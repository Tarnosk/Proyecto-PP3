/**
 * @license
 * SPDX-License-Identifier: Apache-2.0
 */

/**
 * Formatea una fecha/hora asegurando que SIEMPRE incluya tanto la fecha como la hora
 * en formato legible y consistente (YYYY-MM-DD HH:mm:ss o local).
 */
export function formatDateTime(dateInput: string | Date | null | undefined, fallback: string = '-'): string {
  if (!dateInput) return fallback;

  try {
    const raw = String(dateInput).trim();
    if (!raw || raw === '-') return fallback;

    // Si ya viene con formato YYYY-MM-DD HH:mm:ss
    if (/^\d{4}-\d{2}-\d{2}\s\d{2}:\d{2}(:\d{2})?$/.test(raw)) {
      return raw.length === 16 ? `${raw}:00` : raw;
    }

    // Si viene solo YYYY-MM-DD (fecha sin hora de datos antiguos)
    if (/^\d{4}-\d{2}-\d{2}$/.test(raw)) {
      return `${raw} 08:00:00`;
    }

    const d = new Date(raw);
    if (isNaN(d.getTime())) {
      return raw;
    }

    const year = d.getFullYear();
    const month = String(d.getMonth() + 1).padStart(2, '0');
    const day = String(d.getDate()).padStart(2, '0');
    const hours = String(d.getHours()).padStart(2, '0');
    const minutes = String(d.getMinutes()).padStart(2, '0');
    const seconds = String(d.getSeconds()).padStart(2, '0');

    return `${year}-${month}-${day} ${hours}:${minutes}:${seconds}`;
  } catch {
    return String(dateInput);
  }
}

/**
 * Formatea una fecha/hora a un formato extendido amigable (DD/MM/YYYY a las HH:mm hs).
 */
export function formatDateTimeFriendly(dateInput: string | Date | null | undefined, fallback: string = '-'): string {
  if (!dateInput) return fallback;

  try {
    const str = formatDateTime(dateInput, fallback);
    if (str === fallback) return fallback;

    const [fecha, hora] = str.split(' ');
    if (fecha && hora) {
      const [y, m, d] = fecha.split('-');
      const horaCorta = hora.slice(0, 5);
      return `${d}/${m}/${y} ${horaCorta} hs`;
    }
    return str;
  } catch {
    return String(dateInput);
  }
}
