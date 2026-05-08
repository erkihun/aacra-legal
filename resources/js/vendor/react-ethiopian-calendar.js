import { jsx as C, Fragment as Dt, jsxs as X } from "react/jsx-runtime";
import ct, { forwardRef as ue, useRef as rt, useMemo as ge, useCallback as Et, useEffect as lt, isValidElement as ve, cloneElement as Me, useState as ft } from "react";
import { createPortal as we } from "react-dom";
function Ce(t) {
  return t && t.__esModule && Object.prototype.hasOwnProperty.call(t, "default") ? t.default : t;
}
var Tt = { exports: {} }, be = Tt.exports, re;
function xe() {
  return re || (re = 1, function(t, e) {
    (function(o, i) {
      t.exports = i();
    })(be, function() {
      var o = 1e3, i = 6e4, s = 36e5, u = "millisecond", h = "second", d = "minute", $ = "hour", A = "day", Y = "week", O = "month", E = "quarter", k = "year", x = "date", H = "Invalid Date", y = /^(\d{4})[-/]?(\d{1,2})?[-/]?(\d{0,2})[Tt\s]*(\d{1,2})?:?(\d{1,2})?:?(\d{1,2})?[.:]?(\d+)?$/, N = /\[([^\]]+)]|Y{1,4}|M{1,4}|D{1,2}|d{1,4}|H{1,2}|h{1,2}|a|A|m{1,2}|s{1,2}|Z{1,2}|SSS/g, B = { name: "en", weekdays: "Sunday_Monday_Tuesday_Wednesday_Thursday_Friday_Saturday".split("_"), months: "January_February_March_April_May_June_July_August_September_October_November_December".split("_"), ordinal: function(r) {
        var n = ["th", "st", "nd", "rd"], a = r % 100;
        return "[" + r + (n[(a - 20) % 10] || n[a] || n[0]) + "]";
      } }, L = function(r, n, a) {
        var l = String(r);
        return !l || l.length >= n ? r : "" + Array(n + 1 - l.length).join(a) + r;
      }, b = { s: L, z: function(r) {
        var n = -r.utcOffset(), a = Math.abs(n), l = Math.floor(a / 60), c = a % 60;
        return (n <= 0 ? "+" : "-") + L(l, 2, "0") + ":" + L(c, 2, "0");
      }, m: function r(n, a) {
        if (n.date() < a.date()) return -r(a, n);
        var l = 12 * (a.year() - n.year()) + (a.month() - n.month()), c = n.clone().add(l, O), p = a - c < 0, S = n.clone().add(l + (p ? -1 : 1), O);
        return +(-(l + (a - c) / (p ? c - S : S - c)) || 0);
      }, a: function(r) {
        return r < 0 ? Math.ceil(r) || 0 : Math.floor(r);
      }, p: function(r) {
        return { M: O, y: k, w: Y, d: A, D: x, h: $, m: d, s: h, ms: u, Q: E }[r] || String(r || "").toLowerCase().replace(/s$/, "");
      }, u: function(r) {
        return r === void 0;
      } }, g = "en", M = {};
      M[g] = B;
      var f = "$isDayjsObject", w = function(r) {
        return r instanceof z || !(!r || !r[f]);
      }, m = function r(n, a, l) {
        var c;
        if (!n) return g;
        if (typeof n == "string") {
          var p = n.toLowerCase();
          M[p] && (c = p), a && (M[p] = a, c = p);
          var S = n.split("-");
          if (!c && S.length > 1) return r(S[0]);
        } else {
          var j = n.name;
          M[j] = n, c = j;
        }
        return !l && c && (g = c), c || !l && g;
      }, D = function(r, n) {
        if (w(r)) return r.clone();
        var a = typeof n == "object" ? n : {};
        return a.date = r, a.args = arguments, new z(a);
      }, v = b;
      v.l = m, v.i = w, v.w = function(r, n) {
        return D(r, { locale: n.$L, utc: n.$u, x: n.$x, $offset: n.$offset });
      };
      var z = function() {
        function r(a) {
          this.$L = m(a.locale, null, !0), this.parse(a), this.$x = this.$x || a.x || {}, this[f] = !0;
        }
        var n = r.prototype;
        return n.parse = function(a) {
          this.$d = function(l) {
            var c = l.date, p = l.utc;
            if (c === null) return /* @__PURE__ */ new Date(NaN);
            if (v.u(c)) return /* @__PURE__ */ new Date();
            if (c instanceof Date) return new Date(c);
            if (typeof c == "string" && !/Z$/i.test(c)) {
              var S = c.match(y);
              if (S) {
                var j = S[2] - 1 || 0, J = (S[7] || "0").substring(0, 3);
                return p ? new Date(Date.UTC(S[1], j, S[3] || 1, S[4] || 0, S[5] || 0, S[6] || 0, J)) : new Date(S[1], j, S[3] || 1, S[4] || 0, S[5] || 0, S[6] || 0, J);
              }
            }
            return new Date(c);
          }(a), this.init();
        }, n.init = function() {
          var a = this.$d;
          this.$y = a.getFullYear(), this.$M = a.getMonth(), this.$D = a.getDate(), this.$W = a.getDay(), this.$H = a.getHours(), this.$m = a.getMinutes(), this.$s = a.getSeconds(), this.$ms = a.getMilliseconds();
        }, n.$utils = function() {
          return v;
        }, n.isValid = function() {
          return this.$d.toString() !== H;
        }, n.isSame = function(a, l) {
          var c = D(a);
          return this.startOf(l) <= c && c <= this.endOf(l);
        }, n.isAfter = function(a, l) {
          return D(a) < this.startOf(l);
        }, n.isBefore = function(a, l) {
          return this.endOf(l) < D(a);
        }, n.$g = function(a, l, c) {
          return v.u(a) ? this[l] : this.set(c, a);
        }, n.unix = function() {
          return Math.floor(this.valueOf() / 1e3);
        }, n.valueOf = function() {
          return this.$d.getTime();
        }, n.startOf = function(a, l) {
          var c = this, p = !!v.u(l) || l, S = v.p(a), j = function(nt, F) {
            var _ = v.w(c.$u ? Date.UTC(c.$y, F, nt) : new Date(c.$y, F, nt), c);
            return p ? _ : _.endOf(A);
          }, J = function(nt, F) {
            return v.w(c.toDate()[nt].apply(c.toDate("s"), (p ? [0, 0, 0, 0] : [23, 59, 59, 999]).slice(F)), c);
          }, P = this.$W, I = this.$M, Z = this.$D, mt = "set" + (this.$u ? "UTC" : "");
          switch (S) {
            case k:
              return p ? j(1, 0) : j(31, 11);
            case O:
              return p ? j(1, I) : j(0, I + 1);
            case Y:
              var pt = this.$locale().weekStart || 0, St = (P < pt ? P + 7 : P) - pt;
              return j(p ? Z - St : Z + (6 - St), I);
            case A:
            case x:
              return J(mt + "Hours", 0);
            case $:
              return J(mt + "Minutes", 1);
            case d:
              return J(mt + "Seconds", 2);
            case h:
              return J(mt + "Milliseconds", 3);
            default:
              return this.clone();
          }
        }, n.endOf = function(a) {
          return this.startOf(a, !1);
        }, n.$set = function(a, l) {
          var c, p = v.p(a), S = "set" + (this.$u ? "UTC" : ""), j = (c = {}, c[A] = S + "Date", c[x] = S + "Date", c[O] = S + "Month", c[k] = S + "FullYear", c[$] = S + "Hours", c[d] = S + "Minutes", c[h] = S + "Seconds", c[u] = S + "Milliseconds", c)[p], J = p === A ? this.$D + (l - this.$W) : l;
          if (p === O || p === k) {
            var P = this.clone().set(x, 1);
            P.$d[j](J), P.init(), this.$d = P.set(x, Math.min(this.$D, P.daysInMonth())).$d;
          } else j && this.$d[j](J);
          return this.init(), this;
        }, n.set = function(a, l) {
          return this.clone().$set(a, l);
        }, n.get = function(a) {
          return this[v.p(a)]();
        }, n.add = function(a, l) {
          var c, p = this;
          a = Number(a);
          var S = v.p(l), j = function(I) {
            var Z = D(p);
            return v.w(Z.date(Z.date() + Math.round(I * a)), p);
          };
          if (S === O) return this.set(O, this.$M + a);
          if (S === k) return this.set(k, this.$y + a);
          if (S === A) return j(1);
          if (S === Y) return j(7);
          var J = (c = {}, c[d] = i, c[$] = s, c[h] = o, c)[S] || 1, P = this.$d.getTime() + a * J;
          return v.w(P, this);
        }, n.subtract = function(a, l) {
          return this.add(-1 * a, l);
        }, n.format = function(a) {
          var l = this, c = this.$locale();
          if (!this.isValid()) return c.invalidDate || H;
          var p = a || "YYYY-MM-DDTHH:mm:ssZ", S = v.z(this), j = this.$H, J = this.$m, P = this.$M, I = c.weekdays, Z = c.months, mt = c.meridiem, pt = function(F, _, V, W) {
            return F && (F[_] || F(l, p)) || V[_].slice(0, W);
          }, St = function(F) {
            return v.s(j % 12 || 12, F, "0");
          }, nt = mt || function(F, _, V) {
            var W = F < 12 ? "AM" : "PM";
            return V ? W.toLowerCase() : W;
          };
          return p.replace(N, function(F, _) {
            return _ || function(V) {
              switch (V) {
                case "YY":
                  return String(l.$y).slice(-2);
                case "YYYY":
                  return v.s(l.$y, 4, "0");
                case "M":
                  return P + 1;
                case "MM":
                  return v.s(P + 1, 2, "0");
                case "MMM":
                  return pt(c.monthsShort, P, Z, 3);
                case "MMMM":
                  return pt(Z, P);
                case "D":
                  return l.$D;
                case "DD":
                  return v.s(l.$D, 2, "0");
                case "d":
                  return String(l.$W);
                case "dd":
                  return pt(c.weekdaysMin, l.$W, I, 2);
                case "ddd":
                  return pt(c.weekdaysShort, l.$W, I, 3);
                case "dddd":
                  return I[l.$W];
                case "H":
                  return String(j);
                case "HH":
                  return v.s(j, 2, "0");
                case "h":
                  return St(1);
                case "hh":
                  return St(2);
                case "a":
                  return nt(j, J, !0);
                case "A":
                  return nt(j, J, !1);
                case "m":
                  return String(J);
                case "mm":
                  return v.s(J, 2, "0");
                case "s":
                  return String(l.$s);
                case "ss":
                  return v.s(l.$s, 2, "0");
                case "SSS":
                  return v.s(l.$ms, 3, "0");
                case "Z":
                  return S;
              }
              return null;
            }(F) || S.replace(":", "");
          });
        }, n.utcOffset = function() {
          return 15 * -Math.round(this.$d.getTimezoneOffset() / 15);
        }, n.diff = function(a, l, c) {
          var p, S = this, j = v.p(l), J = D(a), P = (J.utcOffset() - this.utcOffset()) * i, I = this - J, Z = function() {
            return v.m(S, J);
          };
          switch (j) {
            case k:
              p = Z() / 12;
              break;
            case O:
              p = Z();
              break;
            case E:
              p = Z() / 3;
              break;
            case Y:
              p = (I - P) / 6048e5;
              break;
            case A:
              p = (I - P) / 864e5;
              break;
            case $:
              p = I / s;
              break;
            case d:
              p = I / i;
              break;
            case h:
              p = I / o;
              break;
            default:
              p = I;
          }
          return c ? p : v.a(p);
        }, n.daysInMonth = function() {
          return this.endOf(O).$D;
        }, n.$locale = function() {
          return M[this.$L];
        }, n.locale = function(a, l) {
          if (!a) return this.$L;
          var c = this.clone(), p = m(a, l, !0);
          return p && (c.$L = p), c;
        }, n.clone = function() {
          return v.w(this.$d, this);
        }, n.toDate = function() {
          return new Date(this.valueOf());
        }, n.toJSON = function() {
          return this.isValid() ? this.toISOString() : null;
        }, n.toISOString = function() {
          return this.$d.toISOString();
        }, n.toString = function() {
          return this.$d.toUTCString();
        }, r;
      }(), U = z.prototype;
      return D.prototype = U, [["$ms", u], ["$s", h], ["$m", d], ["$H", $], ["$W", A], ["$M", O], ["$y", k], ["$D", x]].forEach(function(r) {
        U[r[1]] = function(n) {
          return this.$g(n, r[0], r[1]);
        };
      }), D.extend = function(r, n) {
        return r.$i || (r(n, z, D), r.$i = !0), D;
      }, D.locale = m, D.isDayjs = w, D.unix = function(r) {
        return D(1e3 * r);
      }, D.en = M[g], D.Ls = M, D.p = {}, D;
    });
  }(Tt)), Tt.exports;
}
var Se = xe();
const T = /* @__PURE__ */ Ce(Se);
var Ht = {}, ne;
function De() {
  if (ne) return Ht;
  ne = 1, Object.defineProperty(Ht, "__esModule", {
    value: !0
  });
  var t = /* @__PURE__ */ function() {
    function i(s, u) {
      var h = [], d = !0, $ = !1, A = void 0;
      try {
        for (var Y = s[Symbol.iterator](), O; !(d = (O = Y.next()).done) && (h.push(O.value), !(u && h.length === u)); d = !0)
          ;
      } catch (E) {
        $ = !0, A = E;
      } finally {
        try {
          !d && Y.return && Y.return();
        } finally {
          if ($) throw A;
        }
      }
      return h;
    }
    return function(s, u) {
      if (Array.isArray(s))
        return s;
      if (Symbol.iterator in Object(s))
        return i(s, u);
      throw new TypeError("Invalid attempt to destructure non-iterable instance");
    };
  }(), e = function(s) {
    this.message = s, this.name = "Exception";
  }, o = function(s) {
    var u = Math.floor(s / 100) - Math.floor(s / 400) - 4;
    return (s - 1) % 4 === 3 ? u + 1 : u;
  };
  return Ht.toGregorian = function(s) {
    var u = s.constructor === Array ? s : [].slice.call(arguments);
    if (u.indexOf(0) !== -1 || u.indexOf(null) !== -1 || u.indexOf(void 0) !== -1 || u.length !== 3)
      throw new e("Malformed input can't be converted.");
    var h = t(u, 3), d = h[0], $ = h[1], A = h[2], Y = o(d), O = d + 7, E = [0, 30, 31, 30, 31, 31, 28, 31, 30, 31, 30, 31, 31, 30], k = O + 1;
    (k % 4 === 0 && k % 100 !== 0 || k % 400 === 0) && (E[6] = 29);
    var x = ($ - 1) * 30 + A;
    x <= 37 && d <= 1575 ? (x += 28, E[0] = 31) : x += Y - 1, d - 1 % 4 === 3 && (x += 1);
    for (var H = 0, y = void 0, N = 0; N < E.length; N++)
      if (x <= E[N]) {
        H = N, y = x;
        break;
      } else
        H = N, x -= E[N];
    H > 4 && (O += 1);
    var B = [8, 9, 10, 11, 12, 1, 2, 3, 4, 5, 6, 7, 8, 9];
    return E = B[H], [O, E, y];
  }, Ht.toEthiopian = function(s) {
    var u = s.constructor === Array ? s : [].slice.call(arguments);
    if (u.indexOf(0) !== -1 || u.indexOf(null) !== -1 || u.indexOf(void 0) !== -1 || u.length !== 3)
      throw new e("Malformed input can't be converted.");
    var h = t(u, 3), d = h[0], $ = h[1], A = h[2];
    if ($ === 10 && A >= 5 && A <= 14 && d === 1582)
      throw new e("Invalid Date between 5-14 May 1582.");
    var Y = [0, 31, 28, 31, 30, 31, 30, 31, 31, 30, 31, 30, 31], O = [0, 30, 30, 30, 30, 30, 30, 30, 30, 30, 5, 30, 30, 30, 30];
    (d % 4 === 0 && d % 100 !== 0 || d % 400 === 0) && (Y[2] = 29);
    var E = d - 8;
    E % 4 === 3 && (O[10] = 6);
    for (var k = o(d - 8), x = 0, H = 1; H < $; H++)
      x += Y[H];
    x += A;
    var y = E % 4 === 0 ? 26 : 25;
    d < 1582 || x <= 277 && d === 1582 ? (O[1] = 0, O[2] = y) : (y = k - 3, O[1] = y);
    var N = void 0, B = void 0;
    for (N = 1; N < O.length; N++)
      if (x <= O[N]) {
        B = N === 1 || O[N] === 0 ? x + (30 - y) : x;
        break;
      } else
        x -= O[N];
    N > 10 && (E += 1);
    var L = [0, 4, 5, 6, 7, 8, 9, 10, 11, 12, 13, 1, 2, 3, 4], b = L[N];
    return [E, b, B];
  }, Ht;
}
var q = De();
function ie(t, e) {
  var o = Object.keys(t);
  if (Object.getOwnPropertySymbols) {
    var i = Object.getOwnPropertySymbols(t);
    e && (i = i.filter(function(s) {
      return Object.getOwnPropertyDescriptor(t, s).enumerable;
    })), o.push.apply(o, i);
  }
  return o;
}
function Ft(t) {
  for (var e = 1; e < arguments.length; e++) {
    var o = arguments[e] != null ? arguments[e] : {};
    e % 2 ? ie(Object(o), !0).forEach(function(i) {
      Ne(t, i, o[i]);
    }) : Object.getOwnPropertyDescriptors ? Object.defineProperties(t, Object.getOwnPropertyDescriptors(o)) : ie(Object(o)).forEach(function(i) {
      Object.defineProperty(t, i, Object.getOwnPropertyDescriptor(o, i));
    });
  }
  return t;
}
function Ne(t, e, o) {
  return e in t ? Object.defineProperty(t, e, { value: o, enumerable: !0, configurable: !0, writable: !0 }) : t[e] = o, t;
}
function jt(t, e) {
  return function(o) {
    if (Array.isArray(o)) return o;
  }(t) || function(o, i) {
    var s = o == null ? null : typeof Symbol < "u" && o[Symbol.iterator] || o["@@iterator"];
    if (s != null) {
      var u, h, d = [], $ = !0, A = !1;
      try {
        for (s = s.call(o); !($ = (u = s.next()).done) && (d.push(u.value), !i || d.length !== i); $ = !0) ;
      } catch (Y) {
        A = !0, h = Y;
      } finally {
        try {
          $ || s.return == null || s.return();
        } finally {
          if (A) throw h;
        }
      }
      return d;
    }
  }(t, e) || function(o, i) {
    if (o) {
      if (typeof o == "string") return oe(o, i);
      var s = Object.prototype.toString.call(o).slice(8, -1);
      if (s === "Object" && o.constructor && (s = o.constructor.name), s === "Map" || s === "Set") return Array.from(o);
      if (s === "Arguments" || /^(?:Ui|I)nt(?:8|16|32)(?:Clamped)?Array$/.test(s)) return oe(o, i);
    }
  }(t, e) || function() {
    throw new TypeError(`Invalid attempt to destructure non-iterable instance.
In order to be iterable, non-array objects must have a [Symbol.iterator]() method.`);
  }();
}
function oe(t, e) {
  (e == null || e > t.length) && (e = t.length);
  for (var o = 0, i = new Array(e); o < e; o++) i[o] = t[o];
  return i;
}
function Oe(t, e) {
  var o = t.element, i = t.popper, s = t.position, u = s === void 0 ? "bottom-center" : s, h = t.containerStyle, d = t.containerClassName, $ = d === void 0 ? "" : d, A = t.arrow, Y = t.arrowStyle, O = Y === void 0 ? {} : Y, E = t.arrowClassName, k = E === void 0 ? "" : E, x = t.fixMainPosition, H = t.fixRelativePosition, y = t.offsetY, N = t.offsetX, B = t.animations, L = t.zIndex, b = L === void 0 ? 0 : L, g = t.popperShadow, M = t.onChange, f = t.active, w = f === void 0 || f, m = t.portal, D = t.portalTarget, v = typeof window < "u", z = v && D instanceof HTMLElement, U = A === !0, r = i && w === !0, n = rt(), a = rt(), l = rt(), c = rt(), p = ge(function() {
    return { position: u, fixMainPosition: x, fixRelativePosition: H, offsetY: y, offsetX: N, defaultArrow: U, animations: B, zIndex: b, onChange: M };
  }, [u, x, H, y, N, U, B, M, b]), S = Et(function() {
    l.current && (l.current.style.transition = ""), a.current && (a.current.parentNode.style.transition = "");
  }, []), j = { element: Ft({ display: "inline-block", height: "max-content" }, h), arrow: Ft({ visibility: "hidden", left: "0", top: "0", position: "absolute" }, O), popper: { position: "absolute", left: "0", top: "0", willChange: "transform", visibility: "hidden", zIndex: b } };
  v && !c.current && (c.current = document.createElement("div"), c.current.data = { portal: m, isValidPortalTarget: z }), lt(function() {
    if (m && !z) {
      var P = c.current;
      return document.body.appendChild(P), function() {
        document.body.contains(P) && document.body.removeChild(P);
      };
    }
  }, [m, z]), lt(function() {
    if (!r) return S(), a.current.parentNode.style.visibility = "hidden", void (l.current && (l.current.style.visibility = "hidden"));
    function P(I) {
      I && I.type !== "resize" && !I.target.contains(n.current) || (I && S(), ae(n, a, l, p, I));
    }
    return P(), document.addEventListener("scroll", P, !0), window.addEventListener("resize", P), function() {
      document.removeEventListener("scroll", P, !0), window.removeEventListener("resize", P);
    };
  }, [r, p, S]), lt(function() {
    var P = { portal: m, isValidPortalTarget: z }, I = c.current.data;
    JSON.stringify(P) !== JSON.stringify(I) && (c.current.data = P, n.current.refreshPosition());
  }, [m, z]);
  var J = ct.createElement(ct.Fragment, null, function() {
    if (!A || !r) return null;
    var P = ct.createElement("div", { ref: l, style: j.arrow }), I = ve(A) ? { children: A } : { className: "ep-arrow ".concat(g ? "ep-shadow" : "", " ").concat(k) };
    return Me(P, I);
  }(), ct.createElement("div", { className: g ? "ep-popper-shadow" : "", style: j.popper }, ct.createElement("div", { ref: a }, i)));
  return ct.createElement("div", { ref: function(P) {
    if (P && (P.removeTransition = S, P.refreshPosition = function() {
      return setTimeout(function() {
        return ae(n, a, l, p, {});
      }, 10);
    }), n.current = P, e instanceof Function) return e(P);
    e && (e.current = P);
  }, className: $, style: j.element }, o, m && v ? we(J, z ? D : c.current) : J);
}
var $e = ue(Oe);
function ae(t, e, o, i, s) {
  var u = i.position, h = i.fixMainPosition, d = i.fixRelativePosition, $ = i.offsetY, A = $ === void 0 ? 0 : $, Y = i.offsetX, O = Y === void 0 ? 0 : Y, E = i.defaultArrow, k = i.animations, x = k === void 0 ? [] : k, H = i.zIndex, y = i.onChange;
  if (t.current && e.current) {
    var N, B, L, b, g = (B = window.pageXOffset !== void 0, L = (document.compatMode || "") === "CSS1Compat", { scrollLeft: B ? window.pageXOffset : L ? document.documentElement.scrollLeft : document.body.scrollLeft, scrollTop: B ? window.pageYOffset : L ? document.documentElement.scrollTop : document.body.scrollTop }), M = g.scrollLeft, f = g.scrollTop, w = _t(t.current, M, f), m = w.top, D = w.left, v = w.height, z = w.width, U = w.right, r = w.bottom, n = _t(e.current, M, f), a = n.top, l = n.left, c = n.height, p = n.width, S = document.documentElement, j = S.clientHeight, J = S.clientWidth, P = e.current.parentNode, I = function(tt) {
      if (!tt) return [0, 0];
      var ut = jt((tt.style.transform.match(/translate\((.*?)px,\s(.*?)px\)/) || []).map(function(Q) {
        return Number(Q);
      }), 3), Ct = ut[1], et = Ct === void 0 ? 0 : Ct, yt = ut[2];
      return [et, yt === void 0 ? 0 : yt];
    }(P), Z = jt(I, 2), mt = Z[0], pt = Z[1], St = function(tt) {
      var ut = jt(tt.split("-"), 2), Ct = ut[0], et = Ct === void 0 ? "bottom" : Ct, yt = ut[1], Q = yt === void 0 ? "center" : yt;
      et === "auto" && (et = "bottom"), Q === "auto" && (Q = "center");
      var Lt = et === "top" || et === "bottom", At = et === "left" || et === "right";
      return At && (Q === "start" && (Q = "top"), Q === "end" && (Q = "bottom")), Lt && (Q === "start" && (Q = "left"), Q === "end" && (Q = "right")), [et, Q, Lt, At];
    }(u), nt = jt(St, 4), F = nt[0], _ = nt[1], V = nt[2], W = nt[3], G = F, kt = function(tt, ut) {
      return "translate(".concat(tt, "px, ").concat(ut, "px)");
    }, Bt = z - p, Vt = v - c, vt = _ === "left" ? 0 : _ === "right" ? Bt : Bt / 2, Ot = Bt - vt, Mt = _ === "top" ? 0 : _ === "bottom" ? Vt : Vt / 2, $t = Vt - Mt, it = D - l + mt, ot = m - a + pt, K = 0, R = 0, Pt = It(t.current), Jt = [], wt = o.current, Ut = _t(wt, M, f) || {}, qt = Ut.height, at = qt === void 0 ? 0 : qt, Xt = Ut.width, st = Xt === void 0 ? 0 : Xt, ht = it, dt = ot, Zt = { top: "bottom", bottom: "top", left: "right", right: "left" };
    for (V && (it += vt, ot += F === "top" ? -c : v, E && (at = 11, st = 20)), W && (it += F === "left" ? -p : z, ot += Mt, E && (at = 20, st = 11)); Pt; ) Jt.push(Pt), Qt(_t(Pt, M, f)), Pt = It(Pt.parentNode);
    Qt({ top: f, bottom: f + j, left: M, right: M + J, height: j, width: J }), V && (ot += G === "bottom" ? A : -A), W && (it += G === "right" ? O : -O), it -= K, ot -= R, N = Zt[G], wt && (V && ((b = z < p) ? ht += z / 2 : ht = it + p / 2, ht -= st / 2, G === "bottom" && (dt = ot, ot += at), G === "top" && (dt = (ot -= at) + c), K < 0 && K - vt < 0 && (b ? ht += (vt - K) / 2 : z - vt + K < p && (ht += (z - vt + K - p) / 2)), K > 0 && K + Ot > 0 && (b ? ht -= (K + Ot) / 2 : z - K - Ot < p && (ht -= (z - K - Ot - p) / 2))), W && ((b = v < c) ? dt += v / 2 : dt = ot + c / 2, dt -= at / 2, G === "left" && (ht = (it -= st) + p), G === "right" && (ht = it, it += st), R < 0 && R - Mt < 0 && (b ? dt += (Mt - R) / 2 : v - Mt + R < c && (dt += (v - Mt + R - c) / 2)), R > 0 && R + $t > 0 && (b ? dt -= (R + $t) / 2 : v - R - $t < c && (dt -= (v - R - $t - c) / 2))), wt.setAttribute("direction", N), wt.style.height = at + "px", wt.style.width = st + "px", wt.style.transform = kt(ht, dt), wt.style.visibility = "visible", wt.style.zIndex = H + 1), P.style.transform = kt(it, ot);
    var Kt = { popper: { top: ot, bottom: ot + c, left: it, right: it + p, height: c, width: p }, element: { top: m, bottom: r, left: D, right: U, height: v, width: z }, arrow: { top: dt, bottom: dt + at, left: ht, right: ht + st, height: at, width: st, direction: N }, position: G + "-" + (K !== 0 ? "auto" : _), scroll: { scrollLeft: M, scrollTop: f }, scrollableParents: Jt, event: s };
    s || x.forEach(function(tt) {
      tt({ popper: P, arrow: wt, data: Ft(Ft({}, Kt), {}, { getTransform: kt, mirror: Zt }) });
    }), P.style.visibility = "visible", typeof y == "function" && y(Kt);
  }
  function Qt(tt) {
    var ut = tt.top, Ct = tt.bottom, et = tt.left, yt = tt.right, Q = tt.height, Lt = tt.width;
    if (V) {
      var At = Math.round(m - ut + v / 2), Rt = Math.round(Q / 2);
      h || (m - (c + A + at) < ut && At <= Rt && G === "top" ? (ot += c + v, G = "bottom") : r + c + A + at > Q + ut && At >= Rt && G === "bottom" && (ot -= c + v, G = "top")), d || (D + vt < et && (K = zt(U - st > et ? D + vt - et : -z + vt + st, K)), U - Ot > yt && (K = zt(D + st < yt ? U - Ot - yt : z - Ot - st, K)));
    }
    if (W) {
      var te = Math.round(D - et + z / 2), ee = Math.round(Lt / 2);
      h || (D - (p + O + st) < et && te < ee && G === "left" ? (it += z + p, G = "right") : U + p + O + st > yt && te > ee && G === "right" && (it -= z + p, G = "left")), d || (m + Mt < ut && (R = zt(r - at > ut ? m + Mt - ut : -v + Mt + at, R)), r - $t > Ct && (R = zt(m + at < Ct ? r - $t - Ct : v - $t - at, R)));
    }
  }
}
function _t(t, e, o) {
  if (t) {
    var i = t.getBoundingClientRect(), s = i.top, u = i.left, h = i.width, d = i.height, $ = s + o, A = u + e;
    return { top: $, bottom: $ + d, left: A, right: A + h, width: h, height: d };
  }
}
function It(t) {
  if (t && t.tagName !== "HTML") {
    var e = window.getComputedStyle(t), o = function(i) {
      return ["auto", "scroll"].includes(i);
    };
    return t.clientHeight < t.scrollHeight && o(e.overflowX) || t.clientWidth < t.scrollWidth && o(e.overflowY) ? t : It(t.parentNode);
  }
}
function zt(t, e) {
  return Math.round(Math.abs(t)) > Math.round(Math.abs(e)) ? t : e;
}
const Yt = (t) => t % 4 === 3 ? 6 : 5, Ee = (t, e, o) => {
  const i = q.toGregorian(t, e, o);
  return T().year(i[0]).month(i[1] - 1).date(i[2]).day();
}, bt = (t, e, o, i) => o ? t === 1 ? Yt(e - 1) : 30 : i ? t === 12 ? Yt(e) : 30 : t === 13 ? Yt(e) : 30, ke = (t, e, o) => e === 13 ? [t + 1, 1, o] : [t, e + 1, o], Pe = (t, e, o) => [t + 1, e, o], Ae = (t, e, o) => e === 1 ? [t - 1, 13, 1] : [t, e - 1, o], He = (t, e, o) => [t - 1, e, o], Ye = (t = q.toEthiopian(T().year(), T().month() + 1, T().date())[1], e = q.toEthiopian(T().year(), T().month() + 1, T().date())[2]) => {
  const o = [], i = bt(t, e, !0, !1);
  for (let u = i - Ee(e, t, 1) + 1; u <= i; u++)
    o.push({
      day: u,
      isCurrentMonth: !1
    });
  for (let u = 1; u <= bt(t, e, !1, !1); u++)
    o.push({
      day: u,
      isCurrentMonth: !0,
      today: T().isSame(
        T().year(q.toGregorian(e, t, u)[0]).month(q.toGregorian(e, t, u)[1] - 1).date(q.toGregorian(e, t, u)[2]),
        "day"
      ),
      date: T().year(q.toGregorian(e, t, u)[0]).month(q.toGregorian(e, t, u)[1] - 1).date(q.toGregorian(e, t, u)[2]).startOf("day")
    });
  const s = 42 - o.length;
  for (let u = bt(t, e, !1, !1) + 1; u <= bt(t, e, !1, !1) + s; u++)
    u - bt(t, e, !1, !1) <= bt(t, e, !1, !0) ? o.push({
      day: u - bt(t, e, !1, !1),
      isCurrentMonth: !1
    }) : o.push({
      day: u - (bt(t, e, !1, !1) + bt(t, e, !1, !0)),
      isCurrentMonth: !1
    });
  return o;
}, ce = [
  "መስከረም",
  "ጥቅምት",
  "ኅዳር",
  "ታህሳስ",
  "ጥር",
  "የካቲት",
  "መጋቢት",
  "ሚያዝያ",
  "ግንቦት",
  "ሰኔ",
  "ሐምሌ",
  "ነሐሴ",
  "ጳጉሜ"
], le = [
  "Meskerem",
  "Tikimt",
  "Hidar",
  "Tahsas",
  "Tir",
  "Yekatit",
  "Megabit",
  "Miazia",
  "Genbot",
  "Sene",
  "Hamle",
  "Nehase",
  "Pagume"
], Re = (t) => {
  const e = t.replace(/[-/]/g, "-"), [o, i, s] = e.split("-").map(Number);
  if (!o || !i || !s || isNaN(o) || isNaN(i) || isNaN(s))
    throw new Error(
      'Invalid date format. Please use "yyyy/mm/dd" or "yyyy-mm-dd"'
    );
  const u = q.toEthiopian(o, i, s);
  return {
    year: u[0],
    month: u[1],
    day: u[2],
    monthName: ce[u[1] - 1],
    monthNameEnglish: le[u[1] - 1]
  };
};
var fe = {
  color: void 0,
  size: void 0,
  className: void 0,
  style: void 0,
  attr: void 0
}, se = ct.createContext && ct.createContext(fe), Nt = function() {
  return Nt = Object.assign || function(t) {
    for (var e, o = 1, i = arguments.length; o < i; o++) {
      e = arguments[o];
      for (var s in e) Object.prototype.hasOwnProperty.call(e, s) && (t[s] = e[s]);
    }
    return t;
  }, Nt.apply(this, arguments);
}, Le = function(t, e) {
  var o = {};
  for (var i in t) Object.prototype.hasOwnProperty.call(t, i) && e.indexOf(i) < 0 && (o[i] = t[i]);
  if (t != null && typeof Object.getOwnPropertySymbols == "function") for (var s = 0, i = Object.getOwnPropertySymbols(t); s < i.length; s++)
    e.indexOf(i[s]) < 0 && Object.prototype.propertyIsEnumerable.call(t, i[s]) && (o[i[s]] = t[i[s]]);
  return o;
};
function he(t) {
  return t && t.map(function(e, o) {
    return ct.createElement(e.tag, Nt({
      key: o
    }, e.attr), he(e.child));
  });
}
function xt(t) {
  return function(e) {
    return ct.createElement(je, Nt({
      attr: Nt({}, t.attr)
    }, e), he(t.child));
  };
}
function je(t) {
  var e = function(o) {
    var i = t.attr, s = t.size, u = t.title, h = Le(t, ["attr", "size", "title"]), d = s || o.size || "1em", $;
    return o.className && ($ = o.className), t.className && ($ = ($ ? $ + " " : "") + t.className), ct.createElement("svg", Nt({
      stroke: "currentColor",
      fill: "currentColor",
      strokeWidth: "0"
    }, o.attr, i, h, {
      className: $,
      style: Nt(Nt({
        color: t.color || o.color
      }, o.style), t.style),
      height: d,
      width: d,
      xmlns: "http://www.w3.org/2000/svg"
    }), u && ct.createElement("title", null, u), t.children);
  };
  return se !== void 0 ? ct.createElement(se.Consumer, null, function(o) {
    return e(o);
  }) : e(fe);
}
function de(t) {
  return xt({ attr: { viewBox: "0 0 24 24" }, child: [{ tag: "polyline", attr: { fill: "none", strokeWidth: "2", points: "9 6 15 12 9 18" } }] })(t);
}
function me(t) {
  return xt({ attr: { viewBox: "0 0 24 24" }, child: [{ tag: "polyline", attr: { fill: "none", strokeWidth: "2", points: "9 6 15 12 9 18", transform: "matrix(-1 0 0 1 24 0)" } }] })(t);
}
function pe(t) {
  return xt({ attr: { viewBox: "0 0 24 24" }, child: [{ tag: "polyline", attr: { fill: "none", strokeWidth: "2", points: "7 2 17 12 7 22" } }] })(t);
}
function ye(t) {
  return xt({ attr: { viewBox: "0 0 24 24" }, child: [{ tag: "polyline", attr: { fill: "none", strokeWidth: "2", points: "7 2 17 12 7 22", transform: "matrix(-1 0 0 1 24 0)" } }] })(t);
}
const _e = ({ en: t, am: e, selectedLang: o }) => /* @__PURE__ */ C("div", { children: o === "am" ? e : t });
function Gt(...t) {
  return t.filter(Boolean).join(" ");
}
const ze = ({
  minDateIn: t,
  maxDateIn: e,
  selectedDate: o,
  selectedDateRange: i,
  etToday: s,
  setEtToday: u,
  days: h,
  disableFuture: d,
  disabled: $,
  handleDateChange: A,
  lang: Y,
  isFutureDate: O,
  etCurrentDate: E,
  dateRange: k = !1
}) => {
  const x = ["እ", "ሰ", "ማ", "ረ", "ሐ", "ዓ", "ቅ"], [H, y] = ft(!1), N = rt(null), B = rt(null);
  lt(() => {
    if (N.current && B.current) {
      const g = B.current.offsetTop, M = B.current.offsetHeight, f = N.current.offsetHeight;
      N.current.scrollTop = g - f / 2 + M / 2;
    }
  }, [H]);
  const L = (g) => {
    if (!k || !(i != null && i.startDate) || !(i != null && i.endDate) || !g)
      return !1;
    try {
      const M = T(g).startOf("day"), f = T(i.startDate).startOf("day"), w = T(i.endDate).startOf("day");
      if (!M.isValid() || !f.isValid() || !w.isValid() || !!(d && O(g) || t && M.isBefore(T(t).startOf("day")) || e && M.isAfter(T(e).startOf("day"))))
        return !1;
      const D = M.isSame(f, "day") || M.isAfter(f, "day"), v = M.isSame(w, "day") || M.isBefore(w, "day");
      return D && v;
    } catch (M) {
      return console.warn("Error checking date range:", M), !1;
    }
  }, b = (g, M) => {
    if (!k || !(i != null && i.startDate) || !(i != null && i.endDate) || !M)
      return null;
    try {
      const f = T(g), w = T(i.startDate), m = T(i.endDate);
      return !f.isValid() || !w.isValid() || !m.isValid() ? null : f.isSame(w, "day") ? "start" : f.isSame(m, "day") ? "end" : null;
    } catch (f) {
      return console.warn("Error checking range position:", f), null;
    }
  };
  return /* @__PURE__ */ C(Dt, { children: /* @__PURE__ */ X("div", { className: "calendarContainerEt", children: [
    /* @__PURE__ */ X("div", { className: "topActions", children: [
      /* @__PURE__ */ C("span", { children: /* @__PURE__ */ C(
        "span",
        {
          style: { cursor: "pointer" },
          onClick: () => y(!H),
          children: Y === "am" ? /* @__PURE__ */ X(Dt, { children: [
            ce[s[1] - 1],
            ", ",
            s[0]
          ] }) : /* @__PURE__ */ X(Dt, { children: [
            le[s[1] - 1],
            ", ",
            s[0]
          ] })
        }
      ) }),
      !H && /* @__PURE__ */ X("div", { className: "monthButtons", children: [
        /* @__PURE__ */ C(
          ye,
          {
            onClick: () => u(He(s[0], s[1], s[2])),
            className: "monthButton"
          }
        ),
        /* @__PURE__ */ C(
          me,
          {
            onClick: () => u(Ae(s[0], s[1], s[2])),
            className: "monthButton"
          }
        ),
        /* @__PURE__ */ C(
          "span",
          {
            onClick: () => u(E),
            className: "todayButton",
            children: /* @__PURE__ */ C(_e, { selectedLang: Y, am: "ዛሬ", en: "Today" })
          }
        ),
        /* @__PURE__ */ C(
          de,
          {
            onClick: () => u(ke(s[0], s[1], s[2])),
            className: "monthButton"
          }
        ),
        /* @__PURE__ */ C(
          pe,
          {
            onClick: () => u(Pe(s[0], s[1], s[2])),
            className: "monthButton"
          }
        )
      ] })
    ] }),
    H ? /* @__PURE__ */ C(
      "div",
      {
        className: "yearsGridContainer",
        ref: N,
        style: {
          overflowY: "auto",
          maxHeight: "260px"
        },
        children: /* @__PURE__ */ C(
          "div",
          {
            className: "yearsGrid",
            style: {
              display: "grid",
              gridTemplateColumns: "repeat(4, 1fr)",
              gap: "10px"
            },
            children: Array.from({ length: 200 }, (g, M) => 1900 + M).map(
              (g) => {
                const M = s[0] === g;
                if (!(t && q.toEthiopian(
                  new Date(t).getFullYear(),
                  new Date(t).getMonth() + 1,
                  new Date(t).getDate()
                )[0] > g) && !(e && q.toEthiopian(
                  new Date(e).getFullYear(),
                  new Date(e).getMonth() + 1,
                  new Date(e).getDate()
                )[0] < g))
                  return /* @__PURE__ */ C(
                    "div",
                    {
                      ref: M ? B : null,
                      onClick: (f) => {
                        f.stopPropagation(), y(!1), u([g, s[1], s[2]]);
                      },
                      className: Gt(
                        "yearItem",
                        M ? "backgroundBlue" : ""
                      ),
                      style: {
                        padding: "5px",
                        textAlign: "center"
                      },
                      children: g
                    },
                    g
                  );
              }
            )
          }
        )
      }
    ) : /* @__PURE__ */ X("div", { className: "etHeight", children: [
      /* @__PURE__ */ C("div", { className: "gridSevenEt w-fullEt ", children: Y === "am" ? x.map((g, M) => /* @__PURE__ */ C(
        "span",
        {
          className: "rowHeight dayOfWeek centerGrid",
          children: g
        },
        M
      )) : h.map((g, M) => /* @__PURE__ */ C(
        "span",
        {
          className: "rowHeight dayOfWeek centerGrid",
          children: g
        },
        M
      )) }),
      /* @__PURE__ */ C("div", { className: " gridSevenEt w-fullEt etHeight", children: Ye(s[1], s[0]).map(
        ({ day: g, isCurrentMonth: M, today: f, date: w }, m) => {
          const D = d && O(w), v = o && M && T(o).format("YYYY-MM-DD") === T(w).format("YYYY-MM-DD"), z = M && L(w), U = b(w, M), r = U === "start", n = U === "end";
          return /* @__PURE__ */ C(
            "span",
            {
              onClick: () => {
                M && !$ && !D && (!t || t <= w) && (!e || e >= w) && A(w);
              },
              className: "rowHeight dayText rowHeight centerGrid borderTop",
              children: /* @__PURE__ */ C(
                "span",
                {
                  style: {
                    cursor: !M || $ || D || t && t > w || e && e < w ? "not-allowed" : "pointer"
                  },
                  className: Gt(
                    M ? "" : "grayText",
                    t && t > w ? "grayText" : "",
                    e && e < w ? "grayText" : "",
                    $ ? "grayText" : "",
                    D ? "grayText" : "",
                    f && !v && !z ? "backgroundBlue " : "",
                    "dateWidthAndHeight centerGrid",
                    M ? "currentMonth" : "",
                    v ? "selectedDate" : "",
                    // Date range styling
                    z ? "dateInRange" : "",
                    r ? "rangeStart" : "",
                    n ? "rangeEnd" : ""
                  ),
                  children: g
                }
              )
            },
            m
          );
        }
      ) })
    ] })
  ] }) });
}, Te = (t = T().month(), e = T().year()) => {
  const o = T().year(e).month(t).startOf("month"), i = T().year(e).month(t).endOf("month"), s = [];
  for (let h = 0; h < o.day(); h++) {
    const d = o.date(h);
    q.toEthiopian(
      d.year(),
      d.month() + 1,
      d.date()
    ), s.push({
      day: "",
      date: o.day(h),
      isCurrentMonth: !1,
      etDate: q.toEthiopian(
        d.year(),
        d.month() + 1,
        d.date()
      )
    });
  }
  for (let h = o.date(); h <= i.date(); h++) {
    const d = o.date(h);
    s.push({
      day: "",
      date: o.date(h),
      isCurrentMonth: !0,
      today: T().isSame(o.date(h), "day"),
      etDate: q.toEthiopian(
        d.year(),
        d.month() + 1,
        d.date()
      )
    });
  }
  const u = 42 - s.length;
  for (let h = i.date() + 1; h <= i.date() + u; h++)
    o.date(h), s.push({
      day: "",
      date: i.date(h).startOf("day"),
      firstDateOfMonth: o.date(h),
      isCurrentMonth: !1
    });
  return s;
}, Fe = [
  "January",
  "February",
  "March",
  "April",
  "May",
  "June",
  "July",
  "August",
  "Septemper",
  "October",
  "November",
  "December"
], Wt = (t) => t % 4 === 0 && t % 100 !== 0 || t % 400 === 0, Ge = ({
  minDateIn: t,
  maxDateIn: e,
  selectedDate: o,
  selectedDateRange: i,
  toggleCalendarType: s,
  today: u,
  setToday: h,
  days: d,
  disableFuture: $,
  disabled: A,
  handleDateChange: Y,
  isFutureDate: O,
  currentDate: E,
  dateRange: k = !1
}) => {
  const [x, H] = ft(!1), y = rt(null), N = rt(null);
  lt(() => {
    if (y.current && N.current) {
      const b = N.current.offsetTop, g = N.current.offsetHeight, M = y.current.offsetHeight;
      y.current.scrollTop = b - M / 2 + g / 2;
    }
  }, [x]);
  const B = (b) => {
    if (!k || !(i != null && i.startDate) || !(i != null && i.endDate))
      return !1;
    try {
      const g = T(b), M = T(i.startDate), f = T(i.endDate);
      return !g.isValid() || !M.isValid() || !f.isValid() ? !1 : g.isSameOrAfter(M, "day") && g.isSameOrBefore(f, "day");
    } catch (g) {
      return console.warn("Error checking date range:", g), !1;
    }
  }, L = (b) => {
    if (!k || !(i != null && i.startDate) || !(i != null && i.endDate))
      return null;
    try {
      const g = T(b), M = T(i.startDate), f = T(i.endDate);
      return !g.isValid() || !M.isValid() || !f.isValid() ? null : g.isSame(M, "day") ? "start" : g.isSame(f, "day") ? "end" : null;
    } catch (g) {
      return console.warn("Error checking range position:", g), null;
    }
  };
  return /* @__PURE__ */ C(Dt, { children: /* @__PURE__ */ X("div", { className: "calendarContainerEt", children: [
    /* @__PURE__ */ X("div", { className: "topActions", children: [
      /* @__PURE__ */ X("span", { children: [
        /* @__PURE__ */ C(
          "button",
          {
            onClick: (b) => s(b),
            className: "buttonBackgroundEn buttonStyle"
          }
        ),
        /* @__PURE__ */ X(
          "span",
          {
            style: { cursor: "pointer" },
            onClick: () => H(!x),
            children: [
              Fe[u.month()],
              ", ",
              u.year()
            ]
          }
        )
      ] }),
      !x && /* @__PURE__ */ X("div", { className: "monthButtons", children: [
        /* @__PURE__ */ C(
          ye,
          {
            onClick: () => h(u.year(u.year() - 1)),
            className: "monthButton"
          }
        ),
        /* @__PURE__ */ C(
          me,
          {
            onClick: () => h(u.month(u.month() - 1)),
            className: "monthButton"
          }
        ),
        /* @__PURE__ */ C(
          "span",
          {
            onClick: () => h(E),
            className: "todayButton",
            children: "Today"
          }
        ),
        /* @__PURE__ */ C(
          de,
          {
            onClick: () => h(u.month(u.month() + 1)),
            className: "monthButton"
          }
        ),
        /* @__PURE__ */ C(
          pe,
          {
            onClick: () => h(u.year(u.year() + 1)),
            className: "monthButton"
          }
        )
      ] })
    ] }),
    x ? /* @__PURE__ */ C(
      "div",
      {
        className: "yearsGridContainer",
        ref: y,
        style: {
          overflowY: "auto",
          maxHeight: "260px"
        },
        children: /* @__PURE__ */ C(
          "div",
          {
            className: "yearsGrid",
            style: {
              display: "grid",
              gridTemplateColumns: "repeat(4, 1fr)",
              gap: "10px"
            },
            children: Array.from({ length: 200 }, (b, g) => 1900 + g).map(
              (b) => {
                const g = u.year() === b;
                return t && new Date(t).getFullYear() > b || e && new Date(e).getFullYear() < b ? null : /* @__PURE__ */ C(
                  "div",
                  {
                    ref: g ? N : null,
                    onClick: (M) => {
                      M.stopPropagation(), H(!1), h(u.year(b));
                    },
                    className: Gt(
                      "yearItem",
                      g ? "backgroundBlue" : ""
                    ),
                    style: {
                      padding: "5px"
                    },
                    children: b
                  },
                  b
                );
              }
            )
          }
        )
      }
    ) : /* @__PURE__ */ X("div", { className: "etHeight", children: [
      " ",
      /* @__PURE__ */ C("div", { className: "gridSevenEt w-fullEt", children: d.map((b, g) => /* @__PURE__ */ C("span", { className: "rowHeight dayOfWeek centerGrid", children: b }, g)) }),
      /* @__PURE__ */ C("div", { className: " gridSevenEt w-fullEt", children: Te(u.month(), u.year()).map(
        ({ date: b, isCurrentMonth: g, today: M }, f) => {
          const w = $ && O(b), m = o && new Date(o).getTime() === new Date(b).getTime(), D = B(b), v = L(b), z = v === "start", U = v === "end";
          return /* @__PURE__ */ C(
            "span",
            {
              onClick: () => {
                g && !A && !w && (!t || t <= b) && (!e || e >= b) && Y(b);
              },
              className: "rowHeight dayText rowHeight centerGrid borderTop",
              children: /* @__PURE__ */ C(
                "span",
                {
                  className: Gt(
                    g ? "" : "grayText",
                    t && t >= b ? "grayText" : "",
                    e && e <= b ? "grayText" : "",
                    A ? "grayText" : "",
                    w ? "grayText" : "",
                    M && !m && !D ? "backgroundBlue " : "",
                    "dateWidthAndHeight centerGrid",
                    g ? "currentMonth" : "",
                    m ? "selectedDate" : "",
                    // Date range styling
                    D ? "dateInRange" : "",
                    z ? "rangeStart" : "",
                    U ? "rangeEnd" : ""
                  ),
                  children: b.date()
                }
              )
            },
            f
          );
        }
      ) })
    ] })
  ] }) });
};
function Be(t) {
  return xt({ attr: { viewBox: "0 0 24 24", fill: "none", stroke: "currentColor", strokeWidth: "2", strokeLinecap: "round", strokeLinejoin: "round" }, child: [{ tag: "rect", attr: { x: "3", y: "4", width: "18", height: "18", rx: "2", ry: "2" } }, { tag: "line", attr: { x1: "16", y1: "2", x2: "16", y2: "6" } }, { tag: "line", attr: { x1: "8", y1: "2", x2: "8", y2: "6" } }, { tag: "line", attr: { x1: "3", y1: "10", x2: "21", y2: "10" } }] })(t);
}
const Ve = ue(
  ({
    handleInputClick: t,
    date: e,
    setDate: o,
    handleDateChange: i,
    calendarTypeInt: s,
    style: u,
    disabled: h,
    onBlur: d,
    placeholder: $,
    dateRange: A = !1,
    selectedDateRange: Y = { startDate: null, endDate: null }
  }, O) => {
    const E = rt(null), k = rt(null), x = rt(null), H = (f) => {
      f.target.select();
    }, y = (f) => {
      f.preventDefault(), f.target.focus(), f.target.select();
    }, N = (f, w, m) => {
      if (!s)
        i(
          T().year(f).month(w - 1).date(m).startOf("day")
        );
      else {
        const D = q.toGregorian(f, w, m);
        i(
          T().year(D[0]).month(D[1] - 1).date(D[2]).startOf("day")
        );
      }
    }, B = (f) => f.padStart(2, "0"), L = (f) => {
      const { name: w, value: m } = f.target;
      m && m.length < 2 && w !== "year" && o({ ...e, [w]: B(m) });
    }, b = (f) => {
      ["ArrowLeft", "ArrowRight", "ArrowUp", "ArrowDown"].includes(f.key) && f.preventDefault();
    }, g = (f) => {
      const { name: w, value: m } = f.target;
      isNaN(m) || +m == 0 && m.length > 1 || w === "day" && +e.month == 13 && s && +m > 6 || w === "month" && +e.year && +e.day && +m > 12 && +e.day > Yt(+e.year) && s || w === "day" && (+e.month == 2 || +e.month == 4 || +e.month == 6 || +e.month == 9 || +e.month == 11) && +m > 30 && !s || w === "month" && +e.day && +e.day > 30 && !s && (+m == 2 || +m == 4 || +m == 6 || +m == 9 || +m == 11) || w === "month" && +m == 2 && !s && (+e.day > 29 || +e.day > 28 && !Wt(+e.year)) || w === "day" && +e.month == 2 && !s && (+m > 29 || +m > 28 && !Wt(+e.year)) || w === "year" && +e.month == 2 && !s && m.length === 4 && (+e.day > 29 || +e.day > 28 && !Wt(+m)) || w === "day" && +e.month == 13 && s && +m > 5 && +e.year !== "" && Yt(+e.year) < +m || w === "month" && +m > 13 && s || w === "month" && +m > 12 && !s || w === "day" && +m > 30 && s || w === "day" && +m > 31 || (o({ ...e, [w]: m }), e.year.length === 4 && +e.month > 0 && +e.day > 0 && N(+e.year, +e.month, +e.day), w === "year" && m.length === 4 && +e.month > 0 && +e.day > 0 && N(+m, +e.month, +e.day), w === "month" && +m > 0 && e.year.length === 4 && +e.day > 0 && N(+e.year, +m, +e.day), w === "day" && +m > 0 && e.year.length === 4 && +e.day > 0 && N(+e.year, +e.month, +m), m.length > 0 && w !== "year" && (w === "month" && +m > 1 ? x.current.focus() : w === "day" && m > 3 ? E.current.focus() : w === "month" && m.length === 2 ? x.current.focus() : w === "day" && m.length === 2 && E.current.focus()));
    }, M = () => {
      if (!A || !Y.startDate)
        return "";
      const f = (D) => {
        const v = T(D), [z, U, r] = q.toEthiopian(
          v.year(),
          v.month() + 1,
          v.date()
        );
        return `${r.toString().padStart(2, "0")}/${U.toString().padStart(2, "0")}/${z}`;
      };
      let m = f(Y.startDate);
      if (Y.endDate) {
        const D = f(Y.endDate);
        m += ` - ${D}`;
      } else
        m += " - Select end date";
      return m;
    };
    return /* @__PURE__ */ C(
      "div",
      {
        className: "datePickerContainerEt",
        style: {
          width: "100%",
          minWidth: 0,
          padding: "",
          ...u
        },
        ref: O,
        disabled: h,
        onBlur: (f) => {
          L(f), d == null || d(f);
        },
        children: /* @__PURE__ */ X(
          "div",
          {
            style: {
              display: "flex",
              alignItems: "center",
              justifyContent: "space-between",
              width: "100%",
              minWidth: 0,
              opacity: h ? 0.6 : 1,
              pointerEvents: h ? "none" : "auto"
            },
            onClick: (f) => {
              h || t(f);
            },
            children: [
              A ? (
                // Date range display
                /* @__PURE__ */ C(
                  "div",
                  {
                    style: {
                      display: "flex",
                      alignItems: "center",
                      gap: "4px",
                      flex: 1,
                      minWidth: 0,
                      color: Y.startDate ? "#333" : "#999",
                      fontSize: "14px",
                      overflow: "hidden",
                      textOverflow: "ellipsis",
                      whiteSpace: "nowrap"
                    },
                    children: M() || typeof $ == "string" && $ || "Select date range"
                  }
                )
              ) : (
                // Single date input (existing logic)
                /* @__PURE__ */ X(
                  "div",
                  {
                    style: {
                      display: "flex",
                      alignItems: "center",
                      gap: "4px",
                      flex: "0 1 auto",
                      minWidth: 0,
                      whiteSpace: "nowrap"
                    },
                    children: [
                      /* @__PURE__ */ C(
                        "input",
                        {
                          ref: k,
                          autoComplete: "off",
                          type: "text",
                          value: e.day,
                          onChange: g,
                          onFocus: H,
                          onMouseDown: y,
                          onKeyDown: b,
                          placeholder: "DD",
                          onBlur: (f) => {
                            L(f), d == null || d(f);
                          },
                          maxLength: "2",
                          name: "day",
                          disabled: h,
                          className: "dateInputStyle",
                          style: {
                            width: "2.9ch",
                            textAlign: "left",
                            padding: 0,
                            opacity: h ? 0.6 : 1,
                            cursor: h ? "not-allowed" : "text"
                          }
                        }
                      ),
                      /* @__PURE__ */ C("span", { children: "/" }),
                      /* @__PURE__ */ C(
                        "input",
                        {
                          ref: E,
                          type: "text",
                          value: e.month,
                          onChange: g,
                          onFocus: H,
                          onBlur: (f) => {
                            L(f), d == null || d(f);
                          },
                          onMouseDown: y,
                          onKeyDown: b,
                          placeholder: "MM",
                          maxLength: "2",
                          name: "month",
                          disabled: h,
                          autoComplete: "off",
                          className: "dateInputStyle",
                          style: {
                            width: "3.8ch",
                            textAlign: "left",
                            padding: 0,
                            opacity: h ? 0.6 : 1,
                            cursor: h ? "not-allowed" : "text"
                          }
                        }
                      ),
                      /* @__PURE__ */ C("span", { children: "/" }),
                      /* @__PURE__ */ C(
                        "input",
                        {
                          ref: x,
                          type: "text",
                          value: e.year,
                          onChange: g,
                          onMouseDown: y,
                          onKeyDown: b,
                          onFocus: H,
                          onBlur: (f) => {
                            L(f), d == null || d(f);
                          },
                          placeholder: "YYYY",
                          maxLength: "4",
                          name: "year",
                          disabled: h,
                          className: "dateInputStyle",
                          autoComplete: "off",
                          style: {
                            width: "4.4ch",
                            textAlign: "left",
                            padding: 0,
                            opacity: h ? 0.6 : 1,
                            cursor: h ? "not-allowed" : "text"
                          }
                        }
                      )
                    ]
                  }
                )
              ),
              /* @__PURE__ */ C(
                "div",
                {
                  onClick: (f) => {
                    h || t(f);
                  },
                  style: {
                    cursor: h ? "not-allowed" : "pointer",
                    marginLeft: "8px",
                    opacity: h ? 0.6 : 1
                  },
                  children: /* @__PURE__ */ C(Be, { className: "calendarIcon" })
                }
              )
            ]
          }
        )
      }
    );
  }
), gt = (t) => {
  if (!t) return null;
  const e = T(t);
  return e.isValid() ? e.startOf("day") : null;
}, We = ct.forwardRef(
  ({
    value: t,
    onChange: e,
    calendarType: o = !0,
    minDate: i,
    maxDate: s,
    name: u,
    disabled: h = !1,
    disableFuture: d = !1,
    fullWidth: $,
    borderRadius: A,
    placeholder: Y = !1,
    lang: O = "en",
    label: E = "Date",
    inputStyle: k,
    style: x,
    onBlur: H,
    dateRange: y = !1,
    primaryColor: N = "#0253a5"
  }, B) => {
    const L = gt(i), b = gt(s), [g, M] = ft(null), [f, w] = ft(o), [m, D] = ft(!1), v = T().startOf("day"), z = q.toEthiopian(
      v.year(),
      v.month() + 1,
      v.date()
    ), [U, r] = ft(v), [n, a] = ft(z), [l, c] = ft(
      () => y ? null : gt(t)
    ), [p, S] = ft(() => !y || !t ? { startDate: null, endDate: null } : {
      startDate: gt(t.startDate),
      endDate: gt(t.endDate)
    }), j = rt(null), J = rt(null), P = ["S", "M", "T", "W", "T", "F", "S"];
    lt(() => {
      if (L && b && L.isAfter(b)) {
        const _ = "Invalid date range: minimum date cannot be after maximum date";
        M(_), console.warn(_);
      } else
        M(null);
    }, [L, b]), lt(() => {
      w(o);
    }, [o]), lt(() => {
      y ? t && t.startDate && t.endDate ? S({
        startDate: gt(t.startDate),
        endDate: gt(t.endDate)
      }) : S({ startDate: null, endDate: null }) : c(gt(t));
    }, [t, y]);
    const I = Et(
      (_) => {
        const V = gt(_);
        return V ? V.isAfter(v) : !1;
      },
      [v]
    ), Z = Et(
      (_) => {
        const V = gt(_);
        if (!V) return;
        let W = V;
        if (b && W.isAfter(b) ? W = b : L && W.isBefore(L) && (W = L), y) {
          let G = { ...p };
          if (!G.startDate)
            G.startDate = W, G.endDate = null;
          else if (G.endDate)
            G.startDate = W, G.endDate = null;
          else {
            if (W.isSame(G.startDate, "day"))
              return;
            W.isBefore(G.startDate) ? (G.endDate = G.startDate, G.startDate = W) : G.endDate = W, D(!1);
          }
          if (S(G), e == null || e(G), f) {
            const kt = q.toEthiopian(
              W.year(),
              W.month() + 1,
              W.date()
            );
            a(kt);
          } else
            r(W);
        } else if (c(W), D(!1), e == null || e(W), f) {
          const G = q.toEthiopian(
            W.year(),
            W.month() + 1,
            W.date()
          );
          a(G);
        } else
          r(W);
      },
      [
        y,
        p,
        e,
        f,
        L,
        b
      ]
    ), mt = Et((_) => {
      _.stopPropagation(), w((V) => !V), D(!0);
    }, []), pt = Et(
      (_) => {
        if (_.stopPropagation(), !(g || h)) {
          if (!m) {
            const V = l || p.startDate || L || v;
            if (V)
              if (f) {
                const W = q.toEthiopian(
                  V.year(),
                  V.month() + 1,
                  V.date()
                );
                a(W);
              } else
                r(V);
          }
          D((V) => !V);
        }
      },
      [
        m,
        g,
        h,
        l,
        p.startDate,
        L,
        v,
        f
      ]
    );
    lt(() => {
      function _(V) {
        j.current && !j.current.contains(V.target) && D(!1);
      }
      return document.addEventListener("mousedown", _), () => document.removeEventListener("mousedown", _);
    }, []);
    const nt = Et(() => {
      if (y) {
        if (!p.startDate)
          return { day: "", month: "", year: "" };
        const _ = p.startDate;
        if (f) {
          const V = q.toEthiopian(
            _.year(),
            _.month() + 1,
            _.date()
          );
          return {
            day: String(V[2]).padStart(2, "0"),
            month: String(V[1]).padStart(2, "0"),
            year: String(V[0])
          };
        }
        return {
          day: String(_.date()).padStart(2, "0"),
          month: String(_.month() + 1).padStart(2, "0"),
          year: String(_.year())
        };
      }
      if (!l) return { day: "", month: "", year: "" };
      if (f) {
        const _ = q.toEthiopian(
          l.year(),
          l.month() + 1,
          l.date()
        );
        return {
          day: String(_[2]).padStart(2, "0"),
          month: String(_[1]).padStart(2, "0"),
          year: String(_[0])
        };
      }
      return {
        day: String(l.date()).padStart(2, "0"),
        month: String(l.month() + 1).padStart(2, "0"),
        year: String(l.year())
      };
    }, [y, p, l, f])(), F = { ...x, ...k };
    return /* @__PURE__ */ X(
      "div",
      {
        ref: B,
        style: {
          "--et-primary-color": N,
          width: (F == null ? void 0 : F.width) || ($ ? "100%" : "auto")
        },
        children: [
          /* @__PURE__ */ C(
            $e,
            {
              ref: j,
              zIndex: 1e3,
              containerStyle: { width: "100%" },
              element: /* @__PURE__ */ C(
                Ve,
                {
                  ref: J,
                  fullWidth: $,
                  borderRadius: A,
                  handleInputClick: pt,
                  placeholder: Y,
                  name: u,
                  lang: O,
                  label: E,
                  date: nt,
                  setDate: () => {
                  },
                  handleDateChange: Z,
                  calendarTypeInt: f,
                  showCalendar: m,
                  style: {
                    ...F,
                    borderColor: g ? "#f46a6a" : F == null ? void 0 : F.borderColor,
                    opacity: g ? 0.6 : 1
                  },
                  disabled: h || !!g,
                  onBlur: H,
                  dateRange: y,
                  selectedDateRange: p
                }
              ),
              popper: m && /* @__PURE__ */ C(
                "div",
                {
                  style: {
                    width: (F == null ? void 0 : F.width) || "100%",
                    minWidth: (F == null ? void 0 : F.minWidth) || "220px"
                  },
                  children: /* @__PURE__ */ C(
                    "div",
                    {
                      className: "Cal",
                      style: { width: "100%", minWidth: (F == null ? void 0 : F.minWidth) || "220px" },
                      children: f ? /* @__PURE__ */ C(
                        ze,
                        {
                          minDateIn: L,
                          maxDateIn: b,
                          selectedDate: l,
                          selectedDateRange: p,
                          toggleCalendarType: mt,
                          handleDateChange: Z,
                          disabled: h,
                          disableFuture: d,
                          lang: O,
                          etToday: n,
                          setEtToday: a,
                          days: P,
                          isFutureDate: I,
                          etCurrentDate: z,
                          dateRange: y
                        }
                      ) : /* @__PURE__ */ C(
                        Ge,
                        {
                          minDateIn: L,
                          maxDateIn: b,
                          selectedDate: l,
                          selectedDateRange: p,
                          toggleCalendarType: mt,
                          handleDateChange: Z,
                          disabled: h,
                          disableFuture: d,
                          lang: O,
                          today: U,
                          setToday: r,
                          days: P,
                          isFutureDate: I,
                          currentDate: v,
                          dateRange: y
                        }
                      )
                    }
                  )
                }
              ),
              active: m,
              position: "bottom-start"
            }
          ),
          g && /* @__PURE__ */ C("div", { style: { color: "#f46a6a", fontSize: "12px", marginTop: "4px" }, children: g })
        ]
      }
    );
  }
);
We.displayName = "EtCalendar";
function Ie(t) {
  return xt({ attr: { viewBox: "0 0 24 24" }, child: [{ tag: "path", attr: { fill: "none", d: "M0 0h24v24H0z" } }, { tag: "path", attr: { d: "M11 4V2c0-.55.45-1 1-1s1 .45 1 1v2c0 .55-.45 1-1 1s-1-.45-1-1zm7.36 3.05l1.41-1.42a.996.996 0 10-1.41-1.41l-1.41 1.42a.996.996 0 101.41 1.41zM22 11h-2c-.55 0-1 .45-1 1s.45 1 1 1h2c.55 0 1-.45 1-1s-.45-1-1-1zm-10 8c-.55 0-1 .45-1 1v2c0 .55.45 1 1 1s1-.45 1-1v-2c0-.55-.45-1-1-1zM5.64 7.05L4.22 5.64c-.39-.39-.39-1.03 0-1.41s1.03-.39 1.41 0l1.41 1.41c.39.39.39 1.03 0 1.41s-1.02.39-1.4 0zm11.31 9.9a.996.996 0 000 1.41l1.41 1.41c.39.39 1.03.39 1.41 0a.996.996 0 000-1.41l-1.41-1.41a.996.996 0 00-1.41 0zM2 13h2c.55 0 1-.45 1-1s-.45-1-1-1H2c-.55 0-1 .45-1 1s.45 1 1 1zm3.64 6.78l1.41-1.41c.39-.39.39-1.03 0-1.41s-1.03-.39-1.41 0l-1.41 1.41a.996.996 0 000 1.41c.38.39 1.02.39 1.41 0zM12 6c-3.31 0-6 2.69-6 6s2.69 6 6 6 6-2.69 6-6-2.69-6-6-6z" } }] })(t);
}
function Je(t) {
  return xt({ attr: { viewBox: "0 0 384 512" }, child: [{ tag: "path", attr: { d: "M223.5 32C100 32 0 132.3 0 256S100 480 223.5 480c60.6 0 115.5-24.2 155.8-63.4c5-4.9 6.3-12.5 3.1-18.7s-10.1-9.7-17-8.5c-9.8 1.7-19.8 2.6-30.1 2.6c-96.9 0-175.5-78.8-175.5-176c0-65.8 36-123.1 89.3-153.3c6.1-3.5 9.2-10.5 7.7-17.3s-7.3-11.9-14.3-12.5c-6.3-.5-12.6-.8-19-.8z" } }] })(t);
}
function Ue(t) {
  return xt({ attr: { fill: "currentColor", viewBox: "0 0 16 16" }, child: [{ tag: "path", attr: { d: "M7.646 1.146a.5.5 0 0 1 .708 0l1.5 1.5a.5.5 0 0 1-.708.708L8.5 2.707V4.5a.5.5 0 0 1-1 0V2.707l-.646.647a.5.5 0 1 1-.708-.708l1.5-1.5zM2.343 4.343a.5.5 0 0 1 .707 0l1.414 1.414a.5.5 0 0 1-.707.707L2.343 5.05a.5.5 0 0 1 0-.707zm11.314 0a.5.5 0 0 1 0 .707l-1.414 1.414a.5.5 0 1 1-.707-.707l1.414-1.414a.5.5 0 0 1 .707 0zM11.709 11.5a4 4 0 1 0-7.418 0H.5a.5.5 0 0 0 0 1h15a.5.5 0 0 0 0-1h-3.79zM0 10a.5.5 0 0 1 .5-.5h2a.5.5 0 0 1 0 1h-2A.5.5 0 0 1 0 10zm13 0a.5.5 0 0 1 .5-.5h2a.5.5 0 0 1 0 1h-2a.5.5 0 0 1-.5-.5z" } }] })(t);
}
function qe(t) {
  return xt({ attr: { viewBox: "0 0 24 24" }, child: [{ tag: "path", attr: { d: "M9.8216 2.23796C9.29417 3.38265 9 4.65697 9 6C9 10.9706 13.0294 15 18 15C19.343 15 20.6174 14.7058 21.762 14.1784C20.7678 18.6537 16.7747 22 12 22C6.47715 22 2 17.5228 2 12C2 7.22532 5.3463 3.23221 9.8216 2.23796ZM18.1642 2.29104L19 2.5V3.5L18.1642 3.70896C17.4476 3.8881 16.8881 4.4476 16.709 5.16417L16.5 6H15.5L15.291 5.16417C15.1119 4.4476 14.5524 3.8881 13.8358 3.70896L13 3.5V2.5L13.8358 2.29104C14.5524 2.1119 15.1119 1.5524 15.291 0.835829L15.5 0H16.5L16.709 0.835829C16.8881 1.5524 17.4476 2.1119 18.1642 2.29104ZM23.1642 7.29104L24 7.5V8.5L23.1642 8.70896C22.4476 8.8881 21.8881 9.4476 21.709 10.1642L21.5 11H20.5L20.291 10.1642C20.1119 9.4476 19.5524 8.8881 18.8358 8.70896L18 8.5V7.5L18.8358 7.29104C19.5524 7.1119 20.1119 6.5524 20.291 5.83583L20.5 5H21.5L21.709 5.83583C21.8881 6.5524 22.4476 7.1119 23.1642 7.29104Z" } }] })(t);
}
const Xe = ({
  calendarType: t,
  onTimeChange: e,
  min: o,
  max: i,
  value: s,
  disabled: u,
  error: h
}) => {
  const d = (r) => {
    if (!r) return { hour: "", minute: "" };
    const [n, a] = r.split(":").map(Number);
    return isNaN(n) || isNaN(a) ? { hour: "", minute: "" } : { hour: n, minute: a };
  }, $ = (r) => {
    const { hour: n, minute: a } = d(r);
    if (isNaN(n) || isNaN(a) || n === "" || a === "")
      return { hour: "", minute: "", period: "AM" };
    if (t) {
      if (n >= 0 && n <= 6)
        return { hour: 6 + n, minute: Number(a), period: "PM" };
      if (n > 6 && n <= 18)
        return { hour: n - 6, minute: Number(a), period: "AM" };
      if (n > 18 && n <= 24)
        return { hour: n - 18, minute: Number(a), period: "PM" };
    } else {
      if (n >= 0 && n < 12)
        return { hour: n, minute: Number(a), period: "AM" };
      if (n >= 12 && n < 24)
        return { hour: n - 12, minute: Number(a), period: "PM" };
      if (n === 24)
        return { hour: 12, minute: Number(a), period: "AM" };
    }
  }, {
    hour: A,
    minute: Y,
    period: O
  } = $(s), [E, k] = ft(A), [x, H] = ft(Y), [y, N] = ft(O), B = rt(null), L = rt(!0), b = (r) => {
    r.target.select();
  };
  lt(() => {
    E >= 2 && B.current && B.current.focus();
  }, [E]);
  const g = (r, n, a) => {
    let l = r;
    if (t ? a === "AM" ? l = r % 12 + 6 : l = r % 12 + 18 : (a === "PM" && r !== 12 && (l += 12), a === "AM" && r === 12 && (l = 0)), o) {
      const { hour: c, minute: p } = d(o), S = c * 60 + p;
      if (l * 60 + n < S) return !1;
    }
    if (i) {
      const { hour: c, minute: p } = d(i), S = c * 60 + p;
      if (l * 60 + n > S) return !1;
    }
    return !0;
  }, M = (r) => {
    u || k((n) => {
      if (n === "") {
        let l = r > 0 ? 1 : 12;
        if (r > 0 && o) {
          const { hour: c, minute: p } = d(o);
          g(c, p, y) && (l = t ? (c - 6) % 12 || 12 : c % 12 || 12, N(c >= 12 ? "PM" : "AM"), H(p));
        } else if (r < 0 && i) {
          const { hour: c, minute: p } = d(i);
          g(c, p, y) && (l = t ? (c - 18) % 12 || 12 : c % 12 || 12, N(c >= 12 ? "PM" : "AM"), H(p));
        }
        return l;
      }
      let a = Number(n) + r;
      return a > 12 ? a = 1 : a < 1 && (a = 12), g(a, Number(x), y) ? a : n;
    });
  }, f = (r) => {
    u || H((n) => {
      n === "" && (n = 0);
      let a = Number(n) + r, l = Number(E);
      return a >= 60 ? (a = 0, l = l % 12 + 1) : a < 0 && (a = 59, l = l - 1 < 1 ? 12 : l - 1), g(l, a, y) ? (H(a), k(l), a) : n;
    });
  }, w = (r) => {
    if (u) return;
    const n = r.target.value.replace(/^0+/, "");
    if (n === "" || Number(n) >= 1 && Number(n) <= 12) {
      const a = Number(n);
      g(a, Number(x), y) && k(n);
    }
  }, m = (r) => {
    if (u) return;
    let n;
    if (r.target.value === "0" ? n = 0 : n = r.target.value.replace(/^0+/, ""), n === "" || Number(n) >= 0 && Number(n) < 60) {
      const a = Number(n);
      g(Number(E), a, y) && H(n);
    }
  }, D = () => {
    if (u) return;
    const r = y === "AM" ? "PM" : "AM";
    g(Number(E), Number(x), r) && N(r);
  }, v = () => {
    if (E === "" || x === "") return { hour: "", minute: "" };
    let r = Number(E);
    return t ? y === "AM" ? r = r % 12 + 6 : r = r % 12 + 18 : (y === "PM" && r !== 12 && (r += 12), y === "AM" && r === 12 && (r = 0)), { hour: r % 24, minute: Number(x) };
  };
  lt(() => {
    const { hour: r, minute: n } = v();
    if (r === "" || n === "") {
      e(null);
      return;
    }
    e(v());
  }, [E, x, y]);
  const z = () => {
    let r = Number(E), n = y;
    !t && r !== 0 ? r > 6 && r < 12 && y === "AM" ? (r = r % 12 - 6, n = "PM") : r > 6 && r < 12 && y === "PM" ? (r = r % 12 - 6, n = "AM") : r >= 1 && r < 6 && y === "AM" ? (r = r % 12 + 6, n = "AM") : r >= 1 && r < 6 && y === "PM" ? (r = r % 12 + 6, n = "PM") : r === 12 && y === "AM" ? (r = 6, n = "AM") : r === 12 && y === "PM" ? (r = 6, n = "PM") : r === 6 && y === "AM" ? (r = 12, n = "PM") : r === 6 && y === "PM" && (r = 12, n = "AM") : t && r !== 0 && (r > 6 && r < 12 && y === "AM" ? (n = "AM", r = r - 6) : r >= 1 && r < 6 && y === "PM" ? (n = "AM", r = r + 6) : r >= 1 && r < 6 && y === "AM" ? (n = "PM", r = r + 6) : r > 6 && r < 12 && y === "PM" ? (n = "PM", r = r - 6) : r === 6 && y === "AM" ? (n = "AM", r = 12) : r === 6 && y === "PM" ? (n = "PM", r = 12) : r === 12 && y === "AM" ? (n = "PM", r = 6) : r === 12 && y === "PM" && (n = "AM", r = 6), r <= 0 && (r += 12)), E !== "" && k(r), N(n);
  };
  lt(() => {
    const { hour: r, minute: n, period: a } = $(s);
    k(r), H(n), N(a);
  }, [s]), lt(() => {
    L.current ? L.current = !1 : z();
  }, [t]);
  const U = () => u ? "#CCCCCC" : h ? "#ED4337" : "#555";
  return /* @__PURE__ */ X(
    "div",
    {
      style: {
        display: "flex",
        borderRadius: "15px",
        alignItems: "center",
        justifyContent: "space-between",
        border: `1px solid ${h ? "#ED4337" : "#ccc"}`,
        width: "fit-content",
        paddingLeft: "0.5rem",
        paddingRight: "0.5rem",
        paddingTop: "0.1rem",
        paddingBottom: "0.1rem"
      },
      children: [
        /* @__PURE__ */ X(
          "div",
          {
            style: {
              display: "flex",
              alignItems: "center",
              justifyItems: "center"
            },
            children: [
              /* @__PURE__ */ X(
                "div",
                {
                  style: {
                    display: "flex",
                    flexDirection: "column"
                  },
                  children: [
                    /* @__PURE__ */ C(
                      "button",
                      {
                        type: "button",
                        style: {
                          padding: "4px",
                          backgroundColor: "white",
                          fontSize: "15px",
                          color: u ? "#CCCCCC" : "#555",
                          border: "none",
                          cursor: "pointer"
                        },
                        onClick: () => M(1),
                        children: "+"
                      }
                    ),
                    /* @__PURE__ */ C(
                      "input",
                      {
                        type: "text",
                        className: "no-focus-border",
                        value: E ? E.toString().padStart(2, "0") : "",
                        onFocus: b,
                        placeholder: "--",
                        onChange: w,
                        style: {
                          width: "2rem",
                          textAlign: "center",
                          border: "none",
                          color: U(),
                          fontSize: "20px",
                          appearance: "none",
                          MozAppearance: "textfield"
                        }
                      }
                    ),
                    /* @__PURE__ */ C(
                      "button",
                      {
                        type: "button",
                        style: {
                          padding: "4px",
                          backgroundColor: "white",
                          fontSize: "20px",
                          border: "none",
                          cursor: "pointer",
                          color: u ? "#CCCCCC" : "#555"
                        },
                        onClick: () => M(-1),
                        children: "-"
                      }
                    )
                  ]
                }
              ),
              /* @__PURE__ */ C(
                "span",
                {
                  style: {
                    marginBottom: "0.4rem"
                  },
                  children: ":"
                }
              ),
              /* @__PURE__ */ X(
                "div",
                {
                  style: {
                    display: "flex",
                    flexDirection: "column"
                  },
                  children: [
                    /* @__PURE__ */ C(
                      "button",
                      {
                        type: "button",
                        style: {
                          padding: "4px",
                          backgroundColor: "white",
                          fontSize: "15px",
                          border: "none",
                          cursor: "pointer",
                          color: u ? "#CCCCCC" : "#555"
                        },
                        onClick: () => f(1),
                        children: "+"
                      }
                    ),
                    /* @__PURE__ */ C(
                      "input",
                      {
                        ref: B,
                        type: "text",
                        className: "no-focus-border",
                        value: x === 0 || x ? x.toString().padStart(2, "0") : "",
                        placeholder: "--",
                        onFocus: b,
                        onChange: m,
                        style: {
                          width: "2rem",
                          textAlign: "center",
                          border: "none",
                          fontSize: "20px",
                          color: U(),
                          appearance: "none",
                          MozAppearance: "textfield"
                        }
                      }
                    ),
                    /* @__PURE__ */ C(
                      "button",
                      {
                        type: "button",
                        style: {
                          padding: "4px",
                          backgroundColor: "white",
                          fontSize: "20px",
                          border: "none",
                          cursor: "pointer",
                          color: u ? "#CCCCCC" : "#555"
                        },
                        onClick: () => f(-1),
                        children: "-"
                      }
                    )
                  ]
                }
              )
            ]
          }
        ),
        /* @__PURE__ */ C("div", { children: /* @__PURE__ */ C(
          "button",
          {
            type: "button",
            style: {
              padding: "4px",
              backgroundColor: "white",
              fontSize: `${t ? "30px" : "25px"}`,
              marginLeft: "5px",
              border: "none",
              cursor: "pointer",
              color: u ? "#888888" : "#555"
            },
            onClick: D,
            children: t ? /* @__PURE__ */ X(Dt, { children: [
              " ",
              y === "AM" ? /* @__PURE__ */ C(Dt, { children: E === 12 ? /* @__PURE__ */ C(
                Ue,
                {
                  style: {
                    color: u ? "#CCCCCC" : "#fdb813"
                  }
                }
              ) : /* @__PURE__ */ C(
                Ie,
                {
                  style: {
                    color: u ? "#CCCCCC" : "#fdb813"
                  }
                }
              ) }) : /* @__PURE__ */ C(Dt, { children: E === 12 ? /* @__PURE__ */ C(
                qe,
                {
                  style: {
                    color: u ? "#CCCCCC" : "#1b2f52"
                  }
                }
              ) : /* @__PURE__ */ C(
                Je,
                {
                  style: {
                    color: u ? "#CCCCCC" : "#1b2f52"
                  }
                }
              ) })
            ] }) : /* @__PURE__ */ X(Dt, { children: [
              " ",
              y === "AM" ? "AM" : "PM"
            ] })
          }
        ) })
      ]
    }
  );
}, tr = ({
  value: t = "10:00",
  onChange: e,
  minTime: o = "11:00",
  maxTime: i,
  calendarType: s = !0,
  disabled: u = !1,
  error: h = !1
}) => {
  const d = (k) => {
    if (!k) return { hour: "", minute: "" };
    const [x, H] = k.split(":").map(Number);
    return isNaN(x) || isNaN(H) ? { hour: "", minute: "" } : { hour: x, minute: H };
  }, $ = (k) => {
    if (!k) return null;
    const { hour: x, minute: H } = d(k);
    let y = x;
    if (o) {
      const { hour: N, minute: B } = d(o), L = N * 60 + B;
      if (y * 60 + H < L)
        return !1;
    }
    if (i) {
      const { hour: N, minute: B } = d(i), L = N * 60 + B;
      if (y * 60 + H > L)
        return !1;
    }
    return k;
  }, [A, Y] = ft($(t));
  lt(() => {
    if (!$(t)) {
      Y(null);
      return;
    }
    Y(t);
  }, [o, i, t]);
  const O = (k, x) => {
    const H = k.toString().padStart(2, "0"), y = x.toString().padStart(2, "0");
    return `${H}:${y}`;
  };
  return /* @__PURE__ */ C("div", { children: /* @__PURE__ */ C(
    Xe,
    {
      onTimeChange: (k) => {
        if (!k) return;
        const { hour: x, minute: H } = k, y = O(x, H);
        e && e(y);
      },
      calendarType: s,
      min: o,
      max: i,
      value: A,
      disabled: u,
      error: h
    }
  ) });
};
export {
  We as EtCalendar,
  tr as EtTimePicker,
  Re as convertToEthiopian
};
