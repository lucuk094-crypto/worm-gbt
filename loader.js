/* Veyzen Ops loader — decode & jalankan app.dat saat runtime.
   Kode asli TIDAK dalam bentuk terbaca di file ini atau app.dat,
   supaya tidak langsung bisa dibaca/disalin lewat text editor
   atau tools "web to zip". Harus dijalankan lewat http(s)://,
   TIDAK bisa dibuka langsung dari file lokal (browser blok fetch()
   untuk file:// karena kebijakan CORS). */
(function(){
  var globalEval = eval; /* indirect eval -> jalan di scope global, BUKAN scope terisolasi,
                             supaya semua onclick="..." di HTML tetap bisa panggil fungsinya */
  fetch('app.dat')
    .then(function(r){
      if(!r.ok) throw new Error('HTTP '+r.status);
      return r.text();
    })
    .then(function(b64){
      var bin = atob(b64);
      var bytes = new Uint8Array(bin.length);
      for(var i=0;i<bin.length;i++) bytes[i] = bin.charCodeAt(i);
      var src = new TextDecoder('utf-8').decode(bytes);
      globalEval(src);
    })
    .catch(function(e){
      console.error('[Veyzen] Gagal memuat aplikasi:', e);
      document.body.innerHTML = '<div style="min-height:100vh;display:flex;align-items:center;justify-content:center;background:#000;color:#fff;font-family:sans-serif;text-align:center;padding:24px"><div><h2 style="margin:0 0 8px">Gagal Memuat</h2><p style="color:#999;font-size:13px;max-width:320px">Pastikan halaman ini dibuka lewat http:// atau https:// (bukan dibuka langsung dari file), dan file app.dat ada di folder yang sama.</p></div></div>';
    });
})();
