package org.w3c.tidy;

import java.util.Hashtable;
import java.util.Map;

/* JADX INFO: loaded from: traviaut.jar:org/w3c/tidy/z.class */
public final class z {
    private static z a;
    private static y[] b = {new y("nbsp", 160), new y("iexcl", 161), new y("cent", 162), new y("pound", 163), new y("curren", 164), new y("yen", 165), new y("brvbar", 166), new y("sect", 167), new y("uml", 168), new y("copy", 169), new y("ordf", 170), new y("laquo", 171), new y("not", 172), new y("shy", 173), new y("reg", 174), new y("macr", 175), new y("deg", 176), new y("plusmn", 177), new y("sup2", 178), new y("sup3", 179), new y("acute", 180), new y("micro", 181), new y("para", 182), new y("middot", 183), new y("cedil", 184), new y("sup1", 185), new y("ordm", 186), new y("raquo", 187), new y("frac14", 188), new y("frac12", 189), new y("frac34", 190), new y("iquest", 191), new y("Agrave", 192), new y("Aacute", 193), new y("Acirc", 194), new y("Atilde", 195), new y("Auml", 196), new y("Aring", 197), new y("AElig", 198), new y("Ccedil", 199), new y("Egrave", 200), new y("Eacute", 201), new y("Ecirc", 202), new y("Euml", 203), new y("Igrave", 204), new y("Iacute", 205), new y("Icirc", 206), new y("Iuml", 207), new y("ETH", 208), new y("Ntilde", 209), new y("Ograve", 210), new y("Oacute", 211), new y("Ocirc", 212), new y("Otilde", 213), new y("Ouml", 214), new y("times", 215), new y("Oslash", 216), new y("Ugrave", 217), new y("Uacute", 218), new y("Ucirc", 219), new y("Uuml", 220), new y("Yacute", 221), new y("THORN", 222), new y("szlig", 223), new y("agrave", 224), new y("aacute", 225), new y("acirc", 226), new y("atilde", 227), new y("auml", 228), new y("aring", 229), new y("aelig", 230), new y("ccedil", 231), new y("egrave", 232), new y("eacute", 233), new y("ecirc", 234), new y("euml", 235), new y("igrave", 236), new y("iacute", 237), new y("icirc", 238), new y("iuml", 239), new y("eth", 240), new y("ntilde", 241), new y("ograve", 242), new y("oacute", 243), new y("ocirc", 244), new y("otilde", 245), new y("ouml", 246), new y("divide", 247), new y("oslash", 248), new y("ugrave", 249), new y("uacute", 250), new y("ucirc", 251), new y("uuml", 252), new y("yacute", 253), new y("thorn", 254), new y("yuml", 255), new y("fnof", 402), new y("Alpha", 913), new y("Beta", 914), new y("Gamma", 915), new y("Delta", 916), new y("Epsilon", 917), new y("Zeta", 918), new y("Eta", 919), new y("Theta", 920), new y("Iota", 921), new y("Kappa", 922), new y("Lambda", 923), new y("Mu", 924), new y("Nu", 925), new y("Xi", 926), new y("Omicron", 927), new y("Pi", 928), new y("Rho", 929), new y("Sigma", 931), new y("Tau", 932), new y("Upsilon", 933), new y("Phi", 934), new y("Chi", 935), new y("Psi", 936), new y("Omega", 937), new y("alpha", 945), new y("beta", 946), new y("gamma", 947), new y("delta", 948), new y("epsilon", 949), new y("zeta", 950), new y("eta", 951), new y("theta", 952), new y("iota", 953), new y("kappa", 954), new y("lambda", 955), new y("mu", 956), new y("nu", 957), new y("xi", 958), new y("omicron", 959), new y("pi", 960), new y("rho", 961), new y("sigmaf", 962), new y("sigma", 963), new y("tau", 964), new y("upsilon", 965), new y("phi", 966), new y("chi", 967), new y("psi", 968), new y("omega", 969), new y("thetasym", 977), new y("upsih", 978), new y("piv", 982), new y("bull", 8226), new y("hellip", 8230), new y("prime", 8242), new y("Prime", 8243), new y("oline", 8254), new y("frasl", 8260), new y("weierp", 8472), new y("image", 8465), new y("real", 8476), new y("trade", 8482), new y("alefsym", 8501), new y("larr", 8592), new y("uarr", 8593), new y("rarr", 8594), new y("darr", 8595), new y("harr", 8596), new y("crarr", 8629), new y("lArr", 8656), new y("uArr", 8657), new y("rArr", 8658), new y("dArr", 8659), new y("hArr", 8660), new y("forall", 8704), new y("part", 8706), new y("exist", 8707), new y("empty", 8709), new y("nabla", 8711), new y("isin", 8712), new y("notin", 8713), new y("ni", 8715), new y("prod", 8719), new y("sum", 8721), new y("minus", 8722), new y("lowast", 8727), new y("radic", 8730), new y("prop", 8733), new y("infin", 8734), new y("ang", 8736), new y("and", 8743), new y("or", 8744), new y("cap", 8745), new y("cup", 8746), new y("int", 8747), new y("there4", 8756), new y("sim", 8764), new y("cong", 8773), new y("asymp", 8776), new y("ne", 8800), new y("equiv", 8801), new y("le", 8804), new y("ge", 8805), new y("sub", 8834), new y("sup", 8835), new y("nsub", 8836), new y("sube", 8838), new y("supe", 8839), new y("oplus", 8853), new y("otimes", 8855), new y("perp", 8869), new y("sdot", 8901), new y("lceil", 8968), new y("rceil", 8969), new y("lfloor", 8970), new y("rfloor", 8971), new y("lang", 9001), new y("rang", 9002), new y("loz", 9674), new y("spades", 9824), new y("clubs", 9827), new y("hearts", 9829), new y("diams", 9830), new y("quot", 34), new y("amp", 38), new y("lt", 60), new y("gt", 62), new y("OElig", 338), new y("oelig", 339), new y("Scaron", 352), new y("scaron", 353), new y("Yuml", 376), new y("circ", 710), new y("tilde", 732), new y("ensp", 8194), new y("emsp", 8195), new y("thinsp", 8201), new y("zwnj", 8204), new y("zwj", 8205), new y("lrm", 8206), new y("rlm", 8207), new y("ndash", 8211), new y("mdash", 8212), new y("lsquo", 8216), new y("rsquo", 8217), new y("sbquo", 8218), new y("ldquo", 8220), new y("rdquo", 8221), new y("bdquo", 8222), new y("dagger", 8224), new y("Dagger", 8225), new y("permil", 8240), new y("lsaquo", 8249), new y("rsaquo", 8250), new y("euro", 8364)};
    private Map<String, y> c = new Hashtable();

    private z() {
    }

    public final String a(short s) {
        for (y yVar : this.c.values()) {
            if (yVar.a() == s) {
                return yVar.b();
            }
        }
        return null;
    }

    public final int a(String str) {
        if (str.length() <= 1) {
            return 0;
        }
        if (str.charAt(1) != '#') {
            y yVar = this.c.get(str.substring(1));
            if (yVar != null) {
                return yVar.a();
            }
            return 0;
        }
        int i = 0;
        try {
            if (str.length() >= 4 && str.charAt(2) == 'x') {
                i = Integer.parseInt(str.substring(3), 16);
            } else if (str.length() >= 3) {
                i = Integer.parseInt(str.substring(2));
            }
        } catch (NumberFormatException unused) {
        }
        return i;
    }

    public static z a() {
        if (a == null) {
            a = new z();
            for (int i = 0; i < b.length; i++) {
                z zVar = a;
                y yVar = b[i];
                zVar.c.put(yVar.b(), yVar);
            }
        }
        return a;
    }
}
