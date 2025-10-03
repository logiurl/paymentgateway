(function(){
  function pad(n){ return (n<10?'0':'')+n; }
  function formatCountdown(seconds){
    var m = Math.floor(seconds/60), s = seconds%60; return pad(m)+":"+pad(s);
  }
  function estimateServerNow(){
    var clientAtLoad = Math.floor(Date.now()/1000);
    var offset = clientAtLoad - (UPITimePay && UPITimePay.nowTs ? UPITimePay.nowTs : clientAtLoad);
    return function(){ return Math.floor(Date.now()/1000) - offset; };
  }
  var getServerNow = estimateServerNow();

  function extractTxnId(text){
    if(!text){ return ''; }
    var t = text.replace(/\s+/g,' ').toUpperCase();
    var m;
    m = /UTR[:\s-]*([A-Z0-9]{9,})/i.exec(t); if(m){ return m[1]; }
    m = /TXN(?:\.| )?ID[:\s-]*([A-Z0-9]{8,})/i.exec(t); if(m){ return m[1]; }
    m = /REFERENCE[:\s-]*([A-Z0-9]{10,})/i.exec(t); if(m){ return m[1]; }
    var candidates = t.match(/[A-Z0-9]{12,18}/g) || [];
    if(candidates.length){
      candidates.sort(function(a,b){ return b.length - a.length;});
      return candidates[0];
    }
    return '';
  }

  function setupContainer(container){
    var uniq = container.dataset.uniq;
    var link = container.dataset.link;
    var expiresAt = parseInt(container.dataset.expiresAt || '0', 10);
    var amount = container.dataset.amount || '';
    var ref = container.dataset.ref || '';
    var upiId = container.dataset.upiId || '';
    var payeeName = container.dataset.payeeName || '';
    var note = container.dataset.note || '';
    var sig = container.dataset.sig || '';

    var qrEl = document.getElementById('qr-'+uniq);
    var btnEl = document.getElementById('btn-'+uniq);
    var cdEl = document.getElementById('cd-'+uniq);
    var shotEl = document.getElementById('shot-'+uniq);
    var ocrEl = document.getElementById('ocr-'+uniq);
    var txnEl = document.getElementById('txn-'+uniq);
    var subEl = document.getElementById('sub-'+uniq);
    var msgEl = document.getElementById('msg-'+uniq);

    if(window.QRCode && qrEl){ new QRCode(qrEl, { text: link, width: 220, height: 220 }); }
    if(btnEl){ btnEl.href = link; }

    function tick(){
      var now = getServerNow();
      var remain = Math.max(0, expiresAt - now);
      if(cdEl){ cdEl.textContent = (UPITimePay && UPITimePay.i18n && UPITimePay.i18n.countdownPrefix ? UPITimePay.i18n.countdownPrefix+': ' : 'Time left: ') + formatCountdown(remain); }
      if(remain <= 0){
        if(btnEl){ btnEl.setAttribute('aria-disabled','true'); btnEl.classList.add('disabled'); btnEl.removeAttribute('href'); btnEl.textContent = (UPITimePay && UPITimePay.i18n ? UPITimePay.i18n.expired : 'Expired'); }
        clearInterval(timer);
      }
    }
    var timer = setInterval(tick, 1000); tick();

    if(shotEl){
      shotEl.addEventListener('change', function(){
        var file = shotEl.files && shotEl.files[0];
        if(!file){ return; }
        ocrEl.textContent = (UPITimePay && UPITimePay.i18n ? UPITimePay.i18n.recognizing : 'Recognizing…');
        if(window.Tesseract){
          window.Tesseract.recognize(file, 'eng', {}).then(function(result){
            var text = (result && result.data && result.data.text) ? result.data.text : '';
            var id = extractTxnId(text);
            txnEl.value = id || '';
            ocrEl.textContent = id ? ('ID: '+id) : 'No obvious ID found; please enter manually.';
            container.dataset.ocrText = text || '';
          }).catch(function(){
            ocrEl.textContent = 'OCR failed; please enter ID manually.';
          });
        } else {
          ocrEl.textContent = 'OCR not loaded; please enter ID manually.';
        }
      });
    }

    if(subEl){
      subEl.addEventListener('click', function(){
        // Check expiry
        if(getServerNow() > expiresAt){
          msgEl.textContent = (UPITimePay && UPITimePay.i18n ? UPITimePay.i18n.expired : 'Expired');
          return;
        }
        var file = shotEl && shotEl.files ? shotEl.files[0] : null;
        var extracted = (txnEl && txnEl.value || '').trim();
        if(!file){ msgEl.textContent = 'Please upload a screenshot.'; return; }
        if(!extracted){ msgEl.textContent = 'Please enter the transaction ID.'; return; }

        var fd = new FormData();
        fd.append('action','upi_timepay_submit');
        fd.append('nonce', UPITimePay.nonce);
        fd.append('ref', ref);
        fd.append('expires_at', String(expiresAt));
        fd.append('amount', amount);
        fd.append('note', note);
        fd.append('upi_id', upiId);
        fd.append('payee_name', payeeName);
        fd.append('extracted_id', extracted);
        fd.append('ocr_text', container.dataset.ocrText || '');
        fd.append('sig', sig);
        if(file){ fd.append('screenshot', file, file.name); }

        msgEl.textContent = (UPITimePay && UPITimePay.i18n ? UPITimePay.i18n.uploading : 'Uploading…');
        fetch(UPITimePay.ajaxUrl, { method: 'POST', body: fd, credentials: 'same-origin' })
          .then(function(r){ return r.json(); })
          .then(function(data){
            if(data && data.success){
              if(data.data && data.data.redirect){ window.location.href = data.data.redirect; return; }
              msgEl.textContent = (UPITimePay && UPITimePay.i18n ? UPITimePay.i18n.thankYou : 'Thank you!');
              subEl.disabled = true;
            } else {
              var m = (data && data.data && data.data.message) ? data.data.message : (UPITimePay && UPITimePay.i18n ? UPITimePay.i18n.error : 'Error');
              msgEl.textContent = m;
            }
          })
          .catch(function(){ msgEl.textContent = (UPITimePay && UPITimePay.i18n ? UPITimePay.i18n.error : 'Error'); });
      });
    }
  }

  document.addEventListener('DOMContentLoaded', function(){
    var nodes = document.querySelectorAll('.upi-timepay');
    nodes.forEach(function(n){ setupContainer(n); });
  });
})();
