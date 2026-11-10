import Swal, { type SweetAlertIcon, type SweetAlertPosition } from 'sweetalert2';
import 'sweetalert2/dist/sweetalert2.min.css';

/**
 * Toast Notification Kanan Atas (Top-End)
 * Otomatis menghilang dalam 3 detik, timer progress bar aktif
 */
const TopRightToast = Swal.mixin({
  toast: true,
  position: 'top-end',
  showConfirmButton: false,
  timer: 3500,
  timerProgressBar: true,
  didOpen: (toast) => {
    toast.addEventListener('mouseenter', Swal.stopTimer);
    toast.addEventListener('mouseleave', Swal.resumeTimer);
  },
  customClass: {
    popup: 'geominsel-toast-popup',
    title: 'geominsel-toast-title'
  }
});

export const notify = {
  success(title: string, text?: string) {
    return TopRightToast.fire({
      icon: 'success',
      title: title,
      text: text
    });
  },

  warning(title: string, text?: string) {
    return TopRightToast.fire({
      icon: 'warning',
      title: title,
      text: text
    });
  },

  error(title: string, text?: string) {
    return TopRightToast.fire({
      icon: 'error',
      title: title,
      text: text
    });
  },

  info(title: string, text?: string) {
    return TopRightToast.fire({
      icon: 'info',
      title: title,
      text: text
    });
  }
};

/**
 * Dialog Konfirmasi Kanan Atas (Top-End) atau Modal
 * Mengembalikan Promise<boolean> (true jika ditekan Konfirmasi, false jika Batal)
 */
export async function confirmDialog(options: {
  title: string;
  text?: string;
  confirmButtonText?: string;
  cancelButtonText?: string;
  icon?: SweetAlertIcon;
  position?: SweetAlertPosition;
  isDangerous?: boolean;
}): Promise<boolean> {
  const result = await Swal.fire({
    position: options.position || 'top-end',
    icon: options.icon || 'question',
    title: options.title,
    text: options.text || 'Apakah Anda yakin ingin melanjutkan tindakan ini?',
    showCancelButton: true,
    confirmButtonText: options.confirmButtonText || 'Ya, Lanjutkan',
    cancelButtonText: options.cancelButtonText || 'Batal',
    confirmButtonColor: options.isDangerous ? '#dc2626' : '#2563eb',
    cancelButtonColor: '#64748b',
    reverseButtons: true,
    focusCancel: options.isDangerous ?? false,
    customClass: {
      popup: 'geominsel-confirm-popup',
      title: 'geominsel-confirm-title',
      confirmButton: 'geominsel-btn-confirm',
      cancelButton: 'geominsel-btn-cancel'
    }
  });

  return result.isConfirmed;
}

/**
 * Dialog Input Catatan (Contoh: Catatan Revisi Validasi atau Alasan Penolakan)
 */
export async function promptNoteDialog(options: {
  title: string;
  text?: string;
  inputPlaceholder?: string;
  confirmButtonText?: string;
  required?: boolean;
}): Promise<string | null> {
  const result = await Swal.fire({
    position: 'top-end',
    icon: 'warning',
    title: options.title,
    text: options.text,
    input: 'textarea',
    inputPlaceholder: options.inputPlaceholder || 'Tuliskan catatan teknis atau alasan kebijakan...',
    showCancelButton: true,
    confirmButtonText: options.confirmButtonText || 'Kirim Catatan',
    cancelButtonText: 'Batal',
    confirmButtonColor: '#0284c7',
    cancelButtonColor: '#64748b',
    reverseButtons: true,
    inputValidator: (value) => {
      if (options.required && (!value || !value.trim())) {
        return 'Catatan wajib diisi sebelum melanjutkan!';
      }
      return null;
    },
    customClass: {
      popup: 'geominsel-confirm-popup',
      title: 'geominsel-confirm-title'
    }
  });

  if (result.isConfirmed) {
    return result.value || '';
  }
  return null;
}
