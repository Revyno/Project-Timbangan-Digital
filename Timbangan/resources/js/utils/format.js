export function formatBerat(value) {
  if (value === null || value === undefined) return '0'

  return Number(value).toLocaleString('id-ID', {
    minimumFractionDigits: 0,
    maximumFractionDigits: 0
  })
}

export function formatWeight(value) {
  if (value === null || value === undefined) return '0'
  
  const weightKg = Math.round(Number(value))
  return new Intl.NumberFormat('id-ID').format(weightKg)
}
