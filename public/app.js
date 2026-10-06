const $ = (id) => document.getElementById(id);
let page = 1;
let totalPages = 1;
let editingId = null;

function showError(msg) {
  const el = $("error");
  el.textContent = msg;
  el.classList.remove("hidden");
  setTimeout(() => el.classList.add("hidden"), 5000);
}

async function api(path, opts = {}) {
  const res = await fetch(path, opts);
  const data = await res.json().catch(() => ({}));
  if (!res.ok) throw new Error(data.message || `HTTP ${res.status}`);
  return data;
}

function renderRows(rows) {
  $("rows").innerHTML = rows
    .map(
      (it) => `<tr>
      <td><input type="checkbox" class="pick" value="${it.id}"></td>
      <td>${it.id}</td><td>${it.name}</td><td>${it.barcode ?? "-"}</td>
      <td>${it.category}</td><td>${it.unit}</td><td>${it.quantity}</td><td>${it.stock}</td><td>${it.price}</td>
      <td><button onclick="editItem(${it.id})">Ubah</button> <button onclick="deleteItem(${it.id})">Hapus</button></td>
    </tr>`,
    )
    .join("");
}

async function loadItems() {
  const params = new URLSearchParams({ page });
  if ($("periodFilter").value) params.set("period_id", $("periodFilter").value);
  if ($("barcodeFilter").value)
    params.set("has_barcode", $("barcodeFilter").value);
  try {
    const res = await api(`/api/items?${params}`);
    totalPages = res.meta.total_pages;
    $("pageInfo").textContent =
      `Hal ${res.meta.page}/${totalPages} (${res.meta.total} data)`;
    renderRows(res.data);
  } catch (e) {
    showError(e.message);
  }
}

async function searchBarcode() {
  const code = $("searchBarcode").value.trim();
  if (!code) return showError("Isi kode barcode dulu");
  try {
    const { data } = await api(`/api/barcodes/${encodeURIComponent(code)}`);
    totalPages = 1;
    $("pageInfo").textContent = "Hasil pencarian barcode";
    renderRows([data]);
  } catch (e) {
    showError(e.message);
  }
}

function selectedIds() {
  return [...document.querySelectorAll(".pick:checked")].map((c) => c.value);
}

async function editItem(id) {
  try {
    const { data } = await api(`/api/items/${id}`);
    editingId = id;
    $("dialogTitle").textContent = "Ubah Barang";
    $("fPeriod").value = data.period_id;
    $("fName").value = data.name;
    $("fCategory").value = data.category;
    $("fUnit").value = data.unit;
    $("fQty").value = data.quantity;
    $("fPrice").value = data.price;
    $("itemDialog").showModal();
  } catch (e) {
    showError(e.message);
  }
}

async function deleteItem(id) {
  if (!confirm(`Hapus barang #${id}?`)) return;
  try {
    await api(`/api/items/${id}`, { method: "DELETE" });
    loadItems();
  } catch (e) {
    showError(e.message);
  }
}

$("addBtn").onclick = () => {
  editingId = null;
  $("dialogTitle").textContent = "Tambah Barang";
  $("itemForm").reset();
  $("itemDialog").showModal();
};

$("saveBtn").onclick = async (e) => {
  e.preventDefault();
  const body = {
    period_id: Number($("fPeriod").value),
    name: $("fName").value,
    category: $("fCategory").value,
    unit: $("fUnit").value,
    quantity: Number($("fQty").value),
    price: Number($("fPrice").value),
  };
  try {
    if (editingId) {
      await api(`/api/items/${editingId}`, {
        method: "PATCH",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify(body),
      });
    } else {
      await api("/api/items", {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify(body),
      });
    }
    $("itemDialog").close();
    loadItems();
  } catch (err) {
    showError(err.message);
  }
};

$("generateBtn").onclick = async () => {
  const periodId = $("periodFilter").value || "2";
  try {
    const res = await api(`/api/periods/${periodId}/barcodes`, {
      method: "POST",
    });
    alert(res.message);
    loadItems();
  } catch (e) {
    showError(e.message);
  }
};

$("printBtn").onclick = () => {
  const ids = selectedIds();
  if (!ids.length) return showError("Pilih minimal satu barang via checkbox");
  window.open(
    `/print.html?ids=${ids.join(",")}&copies=${$("copies").value || 1}`,
    "_blank",
  );
};

$("searchBtn").onclick = searchBarcode;
$("searchBarcode").onkeydown = (e) => {
  if (e.key === "Enter") searchBarcode();
};
$("resetBtn").onclick = () => {
  $("searchBarcode").value = "";
  page = 1;
  loadItems();
};
$("selectAll").onchange = (e) =>
  document.querySelectorAll(".pick").forEach((c) => {
    c.checked = e.target.checked;
  });
$("periodFilter").onchange = () => {
  page = 1;
  loadItems();
};
$("barcodeFilter").onchange = () => {
  page = 1;
  loadItems();
};
$("prevBtn").onclick = () => {
  if (page > 1) {
    page--;
    loadItems();
  }
};
$("nextBtn").onclick = () => {
  if (page < totalPages) {
    page++;
    loadItems();
  }
};

loadItems();
