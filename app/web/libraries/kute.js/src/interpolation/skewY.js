export default function(a, b, u, v) {
  return `skewY(${((a + (b - a) * v) * 1000 >> 0 ) / 1000}${u})`
}