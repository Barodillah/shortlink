/**
 * SLink Frontend Application Script
 * Handles responsive sidebar, toast notifications, clipboard copy,
 * QR code modal & download, preview modal, delete confirmation modal, and loading states.
 */
(function () {
  'use strict';

  // 1. Initialize Lucide Icons
  function refreshIcons() {
    if (window.lucide && typeof window.lucide.createIcons === 'function') {
      window.lucide.createIcons();
    }
  }

  document.addEventListener('DOMContentLoaded', function () {
    refreshIcons();
    initSidebar();
    initCopyButtons();
    initModals();
    initLoadingForms();
    initAutoDismissToasts();
  });

  // 2. Responsive Sidebar Toggle (Tablet & Mobile)
  function initSidebar() {
    var openBtn = document.getElementById('sidebar-open-btn');
    var closeBtn = document.getElementById('sidebar-close-btn');
    var backdrop = document.getElementById('sidebar-backdrop');
    var sidebar = document.getElementById('app-sidebar');

    function openSidebar() {
      if (!sidebar || !backdrop) return;
      sidebar.classList.remove('-translate-x-full');
      backdrop.classList.remove('hidden');
      document.body.classList.add('overflow-hidden', 'lg:overflow-auto');
    }

    function closeSidebar() {
      if (!sidebar || !backdrop) return;
      sidebar.classList.add('-translate-x-full');
      backdrop.classList.add('hidden');
      document.body.classList.remove('overflow-hidden', 'lg:overflow-auto');
    }

    if (openBtn) openBtn.addEventListener('click', openSidebar);
    if (closeBtn) closeBtn.addEventListener('click', closeSidebar);
    if (backdrop) backdrop.addEventListener('click', closeSidebar);
  }

  // 3. Toast Notification System
  window.showToast = function (message, type) {
    type = type || 'success';
    var container = document.getElementById('toast-container');
    if (!container) return;

    var toast = document.createElement('div');
    var borderClass = type === 'error' ? 'border-[#E60012]' : 'border-[#171717]';
    var iconName = type === 'error' ? 'alert-circle' : 'check-circle-2';
    var iconColor = type === 'error' ? 'text-[#E60012]' : 'text-[#171717]';

    toast.className =
      'toast-enter flex items-center justify-between gap-3 bg-white border ' +
      borderClass +
      ' px-4 py-3 rounded-md shadow-sm min-w-[280px] max-w-md text-sm text-[#171717]';

    toast.innerHTML =
      '<div class="flex items-center gap-2.5">' +
      '<i data-lucide="' + iconName + '" class="w-4 h-4 shrink-0 ' + iconColor + '"></i>' +
      '<span class="font-medium"></span>' +
      '</div>' +
      '<button type="button" class="text-[#737373] hover:text-[#171717] p-0.5" aria-label="Close">' +
      '<i data-lucide="x" class="w-3.5 h-3.5"></i>' +
      '</button>';

    toast.querySelector('span').textContent = message;
    toast.querySelector('button').addEventListener('click', function () {
      toast.remove();
    });

    container.appendChild(toast);
    refreshIcons();

    setTimeout(function () {
      if (toast.parentNode) {
        toast.remove();
      }
    }, 3500);
  };

  function initAutoDismissToasts() {
    var existingToasts = document.querySelectorAll('[data-auto-dismiss]');
    existingToasts.forEach(function (el) {
      var closeBtn = el.querySelector('[data-toast-close]');
      if (closeBtn) {
        closeBtn.addEventListener('click', function () {
          el.remove();
        });
      }
      setTimeout(function () {
        if (el.parentNode) el.remove();
      }, 4500);
    });
  }

  // 4. Copy Shortlink to Clipboard
  function initCopyButtons() {
    document.addEventListener('click', function (e) {
      var btn = e.target.closest('[data-copy-text]');
      if (!btn) return;

      var text = btn.getAttribute('data-copy-text');
      if (!text) return;

      if (navigator.clipboard && navigator.clipboard.writeText) {
        navigator.clipboard.writeText(text).then(function () {
          window.showToast('Copied to clipboard: ' + text, 'success');
        }).catch(function () {
          fallbackCopy(text);
        });
      } else {
        fallbackCopy(text);
      }
    });
  }

  function fallbackCopy(text) {
    var ta = document.createElement('textarea');
    ta.value = text;
    ta.style.position = 'fixed';
    ta.style.opacity = '0';
    document.body.appendChild(ta);
    ta.select();
    try {
      document.execCommand('copy');
      window.showToast('Copied to clipboard: ' + text, 'success');
    } catch (err) {
      window.showToast('Failed to copy link.', 'error');
    }
    document.body.removeChild(ta);
  }

  // 5. Modals (QR Code, Delete Confirmation, Destination Preview)
  function initModals() {
    // Open QR Modal
    document.addEventListener('click', function (e) {
      var qrBtn = e.target.closest('[data-qr-url]');
      if (qrBtn) {
        var shortUrl = qrBtn.getAttribute('data-qr-url') || '';
        var code = qrBtn.getAttribute('data-qr-code') || '';
        var title = qrBtn.getAttribute('data-qr-title') || ('/' + code);
        openQrModal(shortUrl, code, title);
        return;
      }

      // Open Delete Modal
      var delBtn = e.target.closest('[data-delete-action]');
      if (delBtn) {
        var action = delBtn.getAttribute('data-delete-action');
        var label = delBtn.getAttribute('data-delete-label') || 'this item';
        var returnTo = delBtn.getAttribute('data-return-to') || '';
        openDeleteModal(action, label, returnTo);
        return;
      }

      // Open Preview Modal
      var prevBtn = e.target.closest('[data-preview-url]');
      if (prevBtn) {
        var destUrl = prevBtn.getAttribute('data-preview-url') || '';
        var shortUrlPrev = prevBtn.getAttribute('data-preview-short') || '';
        var titlePrev = prevBtn.getAttribute('data-preview-title') || '';
        openPreviewModal(shortUrlPrev, destUrl, titlePrev);
        return;
      }

      // Close modal triggers
      var closeTrigger = e.target.closest('[data-modal-close]');
      if (closeTrigger) {
        var modalId = closeTrigger.getAttribute('data-modal-close');
        var modalEl = document.getElementById(modalId);
        if (modalEl) modalEl.classList.add('hidden');
      }
    });

    // Escape key closes open modals
    document.addEventListener('keydown', function (e) {
      if (e.key === 'Escape') {
        ['qr-modal', 'delete-modal', 'preview-modal'].forEach(function (id) {
          var m = document.getElementById(id);
          if (m && !m.classList.contains('hidden')) {
            m.classList.add('hidden');
          }
        });
      }
    });
  }

  function openQrModal(shortUrl, code, title) {
    var modal = document.getElementById('qr-modal');
    if (!modal) return;

    var titleEl = document.getElementById('qr-modal-title');
    var urlEl = document.getElementById('qr-modal-url');
    var canvasContainer = document.getElementById('qr-canvas-container');
    var downloadBtn = document.getElementById('qr-download-btn');
    var copyBtn = document.getElementById('qr-copy-btn');

    if (titleEl) titleEl.textContent = title;
    if (urlEl) urlEl.textContent = shortUrl;
    if (copyBtn) copyBtn.setAttribute('data-copy-text', shortUrl);

    if (canvasContainer) {
      canvasContainer.innerHTML = '';
      if (typeof window.qrcode === 'function') {
        try {
          var qr = window.qrcode(0, 'M');
          qr.addData(shortUrl);
          qr.make();
          var moduleCount = qr.getModuleCount();
          var cellSize = Math.max(4, Math.floor(220 / moduleCount));
          var margin = 16;
          var size = moduleCount * cellSize + margin * 2;

          var canvas = document.createElement('canvas');
          canvas.width = size;
          canvas.height = size;
          canvas.className = 'mx-auto border border-[#E5E5E5] rounded-md bg-white p-1';
          var ctx = canvas.getContext('2d');

          ctx.fillStyle = '#FFFFFF';
          ctx.fillRect(0, 0, size, size);

          ctx.fillStyle = '#171717';
          for (var r = 0; r < moduleCount; r++) {
            for (var c = 0; c < moduleCount; c++) {
              if (qr.isDark(r, c)) {
                ctx.fillRect(margin + c * cellSize, margin + r * cellSize, cellSize, cellSize);
              }
            }
          }

          canvasContainer.appendChild(canvas);

          if (downloadBtn) {
            downloadBtn.onclick = function () {
              var a = document.createElement('a');
              a.href = canvas.toDataURL('image/png');
              a.download = 'qr-' + (code || 'shortlink') + '.png';
              document.body.appendChild(a);
              a.click();
              document.body.removeChild(a);
              window.showToast('QR Code PNG downloaded.', 'success');
            };
          }
        } catch (err) {
          canvasContainer.innerHTML = '<p class="text-xs text-[#737373]">Unable to generate QR code for this URL length.</p>';
        }
      }
    }

    modal.classList.remove('hidden');
  }

  function openDeleteModal(actionUrl, label, returnTo) {
    var modal = document.getElementById('delete-modal');
    var form = document.getElementById('delete-modal-form');
    var labelEl = document.getElementById('delete-modal-label');
    var returnInput = document.getElementById('delete-modal-return');

    if (!modal || !form) return;
    form.setAttribute('action', actionUrl);
    if (labelEl) labelEl.textContent = label;
    if (returnInput) returnInput.value = returnTo;
    modal.classList.remove('hidden');
  }

  function openPreviewModal(shortUrl, destUrl, title) {
    var modal = document.getElementById('preview-modal');
    if (!modal) return;

    var titleEl = document.getElementById('preview-modal-title');
    var shortEl = document.getElementById('preview-modal-short');
    var destEl = document.getElementById('preview-modal-dest');
    var openLink = document.getElementById('preview-modal-open');

    if (titleEl) titleEl.textContent = title || 'Destination URL Preview';
    if (shortEl) shortEl.textContent = shortUrl;
    if (destEl) destEl.textContent = destUrl;
    if (openLink) openLink.setAttribute('href', destUrl);

    modal.classList.remove('hidden');
  }

  // 6. Form Submit Loading State
  function initLoadingForms() {
    var forms = document.querySelectorAll('form[data-loading-form]');
    forms.forEach(function (form) {
      form.addEventListener('submit', function () {
        var btn = form.querySelector('button[type="submit"]');
        if (btn && !btn.disabled) {
          btn.disabled = true;
          var loadingText = btn.getAttribute('data-loading-text') || 'Processing...';
          btn.innerHTML = '<span>' + loadingText + '</span>';
        }
      });
    });
  }
})();

