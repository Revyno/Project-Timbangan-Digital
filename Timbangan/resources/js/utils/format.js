export function formatBerat(value) {
  if (value === null || value === undefined) return '0'

  return Number(value).toLocaleString('id-ID', {
    minimumFractionDigits: 0,
    maximumFractionDigits: 1
  })
}
cha
