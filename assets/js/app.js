// Input angka rupiah bebas: ketik 15000 -> tampil 15.000 (server hanya membaca digitnya)
document.querySelectorAll('input[data-rupiah]').forEach(function (inp) {
  function fmt() {
    var d = inp.value.replace(/\D/g, '');
    inp.value = d ? Number(d).toLocaleString('id-ID') : '';
  }
  inp.addEventListener('input', fmt);
  fmt();
});
