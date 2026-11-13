(() => {
  const video = document.getElementById('video');
  const overlay = document.getElementById('overlay');
  if (!video || !overlay) return;

  const $ = id => document.getElementById(id);
  const resultBox = $('result');
  const statusBox = $('detectStatus');
  const modelReady = typeof MODEL_READY === 'undefined' ? true : MODEL_READY;
  const grab = document.createElement('canvas');

  let stream = null;
  let facing = 'environment';
  let busy = false;
  let timer = null;
  let liveKey = '';

  const escapeHtml = value => String(value).replace(/[&<>"']/g, char => ({
    '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;',
  })[char]);

  function setStatus(message, kind = 'info') {
    if (!statusBox) return;
    if (!message) {
      statusBox.className = 'alert d-none';
      statusBox.textContent = '';
      return;
    }
    statusBox.className = `alert alert-${kind}`;
    statusBox.innerHTML = message;
  }

  async function checkDetectionStatus() {
    if (!statusBox) return;
    try {
      const response = await fetch('/api/detection-status', { headers: { Accept: 'application/json' } });
      if (!response.ok) return;
      const status = await response.json();

      if (status.primary_provider === 'roboflow') {
        if (status.roboflow_online) {
          setStatus('<i class="bi bi-cloud-check"></i> Roboflow is online. Scans use Roboflow.', 'success');
          window.setTimeout(() => setStatus(''), 4000);
        } else if (status.roboflow_configured) {
          setStatus('<i class="bi bi-cloud-slash"></i> Roboflow is configured but could not be reached. Scans may fail until it is back online.', 'warning');
        } else {
          setStatus('<i class="bi bi-exclamation-triangle"></i> Roboflow is not configured. Add the API key and workflow URLs to <code>.env</code>.', 'warning');
        }
      } else if (!status.ready) {
        setStatus('<i class="bi bi-exclamation-triangle"></i> No detection provider is available. Start the local service with <code>start_inference.bat</code> or configure Roboflow.', 'warning');
      } else if (status.local_configured && !status.local_online && status.roboflow_configured) {
        setStatus('<i class="bi bi-cloud"></i> The local detection service is offline, so scans will use Roboflow.', 'info');
      } else if (status.local_online) {
        setStatus('<i class="bi bi-check-circle"></i> Local detection service is ready.', 'success');
        window.setTimeout(() => setStatus(''), 4000);
      }
    } catch (error) {
      // Status is advisory only; scanning still works if a provider is reachable.
    }
  }

  function showResult(payload) {
    if (!payload || !resultBox) return;

    if (!payload.ok) {
      resultBox.innerHTML = `<div class="alert alert-danger">${escapeHtml(payload.error || 'The scan failed. Please try again.')}</div>`;
      return;
    }

    if (!payload.detections.length) {
      resultBox.innerHTML = '<div class="alert alert-info">No pest detected. Move closer, improve lighting, and try again.</div>';
      return;
    }

    const top = payload.detections[0];
    const advice = (payload.advice || []).map(item =>
      `<li><b>${escapeHtml(item.title)}</b><br><span class="small">${escapeHtml(item.content)}</span></li>`
    ).join('');
    const severity = ['low', 'medium', 'high'].includes(payload.severity) ? payload.severity : 'low';

    resultBox.innerHTML = `<div class="card p-3"><h5 class="mb-1">${escapeHtml(top.pest_name)}</h5>
      <div class="mb-2"><span class="badge sev-${severity}">${severity.toUpperCase()}</span>
      ${Math.round(top.confidence * 100)}% confidence &middot; ${payload.detections.length} found</div>
      ${advice ? `<ul class="ps-3">${advice}</ul>` : ''}
      ${payload.saved ? `<a class="btn btn-pc btn-sm" href="${escapeHtml(payload.url)}">View full result</a>` : ''}</div>`;
  }

  async function startCamera() {
    if (!navigator.mediaDevices?.getUserMedia) {
      showResult({ ok: false, error: 'Camera access needs HTTPS or localhost. You can still upload a photo.' });
      return;
    }

    stopStream();

    try {
      stream = await navigator.mediaDevices.getUserMedia({
        video: { facingMode: facing, width: { ideal: 1280 }, height: { ideal: 720 } },
        audio: false,
      });
    } catch (error) {
      showResult({ ok: false, error: 'Could not open the camera: ' + error.message });
      return;
    }

    video.srcObject = stream;
    await video.play();
    $('camOff').style.display = 'none';
    $('btnFlip').disabled = false;
    $('btnScan').disabled = !modelReady;
    $('live').disabled = !modelReady;
    $('btnStart').innerHTML = '<i class="bi bi-camera-video-off"></i> Stop camera';
  }

  function stopStream() {
    stream?.getTracks().forEach(track => track.stop());
    stream = null;
    $('live').checked = false;
    setLive(false);
  }

  function stopCamera() {
    stopStream();
    video.srcObject = null;
    $('camOff').style.display = '';
    $('btnFlip').disabled = true;
    $('btnScan').disabled = true;
    $('live').disabled = true;
    $('btnStart').innerHTML = '<i class="bi bi-camera-video"></i> Start camera';
    clearBoxes();
  }

  function frameBlob() {
    grab.width = video.videoWidth;
    grab.height = video.videoHeight;
    grab.getContext('2d').drawImage(video, 0, 0);

    return new Promise(resolve => grab.toBlob(resolve, 'image/jpeg', 0.85));
  }

  function clearBoxes() {
    overlay.getContext('2d').clearRect(0, 0, overlay.width, overlay.height);
  }

  function drawBoxes(detections) {
    overlay.width = video.clientWidth;
    overlay.height = video.clientHeight;

    const context = overlay.getContext('2d');
    context.clearRect(0, 0, overlay.width, overlay.height);
    context.lineWidth = 3;
    context.font = '600 14px Inter, sans-serif';

    detections.forEach(detection => {
      const [x1, y1, x2, y2] = detection.box;
      const width = overlay.width;
      const height = overlay.height;

      context.strokeStyle = '#d4a73a';
      context.strokeRect(x1 * width, y1 * height, (x2 - x1) * width, (y2 - y1) * height);

      const label = `${detection.pest_name} ${Math.round(detection.confidence * 100)}%`;
      const labelWidth = context.measureText(label).width + 10;

      context.fillStyle = '#1a3d2b';
      context.fillRect(x1 * width, Math.max(0, y1 * height - 22), labelWidth, 22);
      context.fillStyle = '#fff';
      context.fillText(label, x1 * width + 5, Math.max(15, y1 * height - 6));
    });
  }

  async function send(blob, save, source) {
    const form = new FormData();
    form.append('image', blob, 'scan.jpg');
    form.append('save', save ? '1' : '0');
    form.append('source', source);
    form.append('hint', $('hint').value);
    form.append('severity', $('severity').value);

    const csrf = document.querySelector('meta[name="csrf-token"]')?.content || '';
    let response;

    try {
      response = await fetch('/api/detect', {
        method: 'POST',
        body: form,
        headers: { 'X-CSRF-Token': csrf, Accept: 'application/json' },
      });
    } catch (error) {
      return { ok: false, error: 'Network error. Check your connection and try again.' };
    }

    if (response.status === 401) {
      window.location.href = '/login';
      return null;
    }

    if (response.status === 419) {
      window.location.reload();
      return null;
    }

    const text = await response.text();

    try {
      return JSON.parse(text);
    } catch (error) {
      return { ok: false, error: `The server returned an unexpected response (HTTP ${response.status}).` };
    }
  }

  function setLive(on) {
    clearInterval(timer);
    timer = null;

    if (on) {
      timer = setInterval(async () => {
        if (busy || !stream) return;
        busy = true;

        try {
          const payload = await send(await frameBlob(), false, 'camera');

          if (!payload) return;

          if (payload.ok) {
            drawBoxes(payload.detections);
            $('liveStatus').textContent = payload.detections.length
              ? `Detected: ${payload.detections.map(item => item.pest_name).join(', ')}`
              : 'Scanning... nothing detected yet';

            const key = [...new Set(payload.detections.map(item => item.pest_name))].sort().join('|');
            if (key !== liveKey) {
              liveKey = key;
              if (payload.detections.length) showResult(payload);
              else resultBox.innerHTML = '';
            }
          } else {
            $('liveStatus').textContent = payload.error;
            $('live').checked = false;
            setLive(false);
          }
        } catch (error) {
          $('liveStatus').textContent = 'Network error';
        } finally {
          busy = false;
        }
      }, 1300);
    } else {
      liveKey = '';
      $('liveStatus').innerHTML = '&nbsp;';
    }
  }

  $('btnStart').addEventListener('click', () => (stream ? stopCamera() : startCamera()));

  $('btnFlip').addEventListener('click', () => {
    facing = facing === 'environment' ? 'user' : 'environment';
    startCamera();
  });

  $('live').addEventListener('change', event => setLive(event.target.checked));

  $('btnScan').addEventListener('click', async () => {
    if (!stream || busy) return;

    busy = true;
    const button = $('btnScan');
    button.disabled = true;
    button.innerHTML = '<span class="spinner-border spinner-border-sm"></span> Scanning...';

    try {
      const payload = await send(await frameBlob(), true, 'camera');
      if (payload) {
        if (payload.ok) drawBoxes(payload.detections);
        showResult(payload);
      }
    } catch (error) {
      showResult({ ok: false, error: 'Network error. Please try again.' });
    } finally {
      busy = false;
      button.disabled = false;
      button.innerHTML = '<i class="bi bi-upc-scan"></i> Scan &amp; Save';
    }
  });

  $('file').addEventListener('change', async event => {
    const file = event.target.files[0];
    if (!file) return;

    const input = event.target;
    input.disabled = true;
    resultBox.innerHTML = '<div class="text-muted"><span class="spinner-border spinner-border-sm"></span> Analyzing photo...</div>';

    try {
      const payload = await send(file, true, 'upload');
      if (payload) showResult(payload);
    } catch (error) {
      showResult({ ok: false, error: 'Network error. Please try again.' });
    } finally {
      input.disabled = !modelReady;
      input.value = '';
    }
  });

  if (!modelReady) $('file').disabled = true;

  checkDetectionStatus();
})();
