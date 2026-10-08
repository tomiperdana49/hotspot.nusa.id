/*
 * MD5 (RFC 1321) for MikroTik HTTP-CHAP login. hexMD5() treats each
 * character as one byte, matching the octal-escaped $(chap-id) and
 * $(chap-challenge) strings the router puts into login.html.
 */
function hexMD5(s) {
    function add(x, y) {
        var l = (x & 0xFFFF) + (y & 0xFFFF);
        return (((x >> 16) + (y >> 16) + (l >> 16)) << 16) | (l & 0xFFFF);
    }
    function rol(n, c) { return (n << c) | (n >>> (32 - c)); }
    function cmn(q, a, b, x, s, t) { return add(rol(add(add(a, q), add(x, t)), s), b); }
    function ff(a, b, c, d, x, s, t) { return cmn((b & c) | (~b & d), a, b, x, s, t); }
    function gg(a, b, c, d, x, s, t) { return cmn((b & d) | (c & ~d), a, b, x, s, t); }
    function hh(a, b, c, d, x, s, t) { return cmn(b ^ c ^ d, a, b, x, s, t); }
    function ii(a, b, c, d, x, s, t) { return cmn(c ^ (b | ~d), a, b, x, s, t); }

    var len = s.length * 8, x = [], i;
    for (i = 0; i < len; i += 8) x[i >> 5] |= (s.charCodeAt(i / 8) & 0xFF) << (i % 32);
    x[len >> 5] |= 0x80 << (len % 32);
    x[(((len + 64) >>> 9) << 4) + 14] = len;

    var K = [], S = [7, 12, 17, 22, 5, 9, 14, 20, 4, 11, 16, 23, 6, 10, 15, 21];
    for (i = 0; i < 64; i++) K[i] = Math.floor(Math.abs(Math.sin(i + 1)) * 4294967296) | 0;
    var F = [ff, gg, hh, ii];

    var a = 1732584193, b = -271733879, c = -1732584194, d = 271733878;
    for (var blk = 0; blk < x.length; blk += 16) {
        var oa = a, ob = b, oc = c, od = d;
        for (i = 0; i < 64; i++) {
            var r = i >> 4, g;
            if (r === 0) g = i;
            else if (r === 1) g = (5 * i + 1) % 16;
            else if (r === 2) g = (3 * i + 5) % 16;
            else g = (7 * i) % 16;
            var t = F[r](a, b, c, d, x[blk + g] | 0, S[r * 4 + (i % 4)], K[i]);
            a = d; d = c; c = b; b = t;
        }
        a = add(a, oa); b = add(b, ob); c = add(c, oc); d = add(d, od);
    }

    var hex = '0123456789abcdef', out = '', w = [a, b, c, d];
    for (i = 0; i < 16; i++) {
        var byte = (w[i >> 2] >> ((i % 4) * 8)) & 0xFF;
        out += hex.charAt(byte >> 4) + hex.charAt(byte & 0xF);
    }
    return out;
}
