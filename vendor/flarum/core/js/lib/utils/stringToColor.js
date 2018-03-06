function hsvToRgb(h, s, v) {
  let r;
  let g;
  let b;

  const i = Math.floor(h * 6);
  const f = h * 6 - i;
  const p = v * (1 - s);
  const q = v * (1 - f * s);
  const t = v * (1 - (1 - f) * s);

  switch (i % 6) {
    case 0: r = v; g = t; b = p; break;
    case 1: r = q; g = v; b = p; break;
    case 2: r = p; g = v; b = t; break;
    case 3: r = p; g = q; b = v; break;
    case 4: r = t; g = p; b = v; break;
    case 5: r = v; g = p; b = q; break;
  }

  return {
    r: Math.floor(r * 255),
    g: Math.floor(g * 255),
    b: Math.floor(b * 255)
  };
}

/**
 * Convert the given string to a unique color.
 *
 * @param {String} string
 * @return {String}
 */
export default function stringToColor(string) {
  let num = 0;

  // Convert the username into a number based on the ASCII value of each
  // character.
  for (let i = 0; i < string.length; i++) {
    num += string.charCodeAt(i);
  }

  // Construct a color using the remainder of that number divided by 360, and
  // some predefined saturation and value values.
  const hue = num % 360;
  const rgb = hsvToRgb(hue / 360, 0.3, 0.9);

  return '' + rgb.r.toString(16) + rgb.g.toString(16) + rgb.b.toString(16);
}
