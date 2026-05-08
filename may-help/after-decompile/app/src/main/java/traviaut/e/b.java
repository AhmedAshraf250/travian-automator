package traviaut.e;

import java.util.Random;

/* JADX INFO: loaded from: traviaut.jar:traviaut/e/b.class */
public final class b {
    private static final Random a = new Random();
    private static final String[] b = {"53.0.2785", "54.0.2840.71"};
    private static final String[] c = {"48.0.2", "49.0.1"};
    private static final String[] d = {"9.1.2", "9.1.3", "10.0"};
    private static final String[] e = {"601.7.1", "601.7.8", "602.1.50"};
    private static final String[] f = {"10_11_4", "10_11_5", "10_11_6"};

    private static String a(String[] strArr) {
        return strArr[a.nextInt(strArr.length)];
    }

    private static void a(StringBuilder sb, String str) {
        sb.append(str);
        if (str.isEmpty()) {
            return;
        }
        sb.append(" ");
    }

    public static String a() {
        StringBuilder sb = new StringBuilder("Mozilla/5.0");
        sb.append(" (");
        int iNextInt = a.nextInt(100);
        if (iNextInt < 90) {
            a(sb, "Windows NT");
            if (iNextInt < 50) {
                sb.append("6.1");
            } else if (iNextInt < 60) {
                sb.append("6.3");
            } else {
                sb.append("10.0");
            }
            if (a.nextInt(4) < 3) {
                sb.append("; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/");
                a(sb, a(b));
                sb.append("Safari/537.36");
            } else {
                sb.append("; ");
                sb.append(a.nextBoolean() ? "Win64; x64;" : "WOW64;");
                sb.append(" rv:");
                String strA = a(c);
                sb.append(strA);
                sb.append(") Gecko/20100101 Firefox/");
                sb.append(strA);
            }
        } else {
            a(sb, "Macintosh; Intel Mac OS X");
            sb.append(a(f));
            sb.append(") ");
            int iNextInt2 = a.nextInt(3);
            sb.append("AppleWebKit/");
            a(sb, e[iNextInt2]);
            a(sb, "(KHTML, like Gecko)");
            sb.append("Version/");
            a(sb, d[iNextInt2]);
            sb.append("Safari/");
            a(sb, e[iNextInt2]);
        }
        return sb.toString();
    }
}
