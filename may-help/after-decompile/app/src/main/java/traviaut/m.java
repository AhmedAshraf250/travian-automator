package traviaut;

import java.util.Arrays;
import java.util.Properties;
import java.util.Random;
import java.util.concurrent.TimeUnit;
import java.util.regex.Matcher;
import java.util.regex.Pattern;
import java.util.stream.Collectors;
import org.w3c.tidy.Tidy;

/* JADX INFO: loaded from: traviaut.jar:traviaut/m.class */
public abstract class m {
    public static final Random a = new Random(System.currentTimeMillis());

    public static Tidy a() {
        Tidy tidy = new Tidy();
        tidy.c(true);
        tidy.b(false);
        tidy.a(true);
        Properties properties = new Properties();
        properties.setProperty("char-encoding", "utf8");
        tidy.a(properties);
        return tidy;
    }

    public static org.a.c a(org.a.c cVar) throws traviaut.b.i {
        org.a.c cVarB = cVar.b("response");
        if (cVarB != null) {
            cVar = cVarB;
        }
        String strC = cVar.c("error");
        String strC2 = cVar.c("errorMsg");
        if (strC.equals("false")) {
            return cVar.b("data");
        }
        throw new traviaut.b.i("system error: " + strC2, 1);
    }

    public static long a(String str) {
        String[] strArrSplit = str.split(":");
        long j = 0;
        int i = 1;
        try {
            for (int length = strArrSplit.length - 1; length >= 0; length--) {
                j += (long) (i * Integer.parseInt(strArrSplit[length]));
                i *= 60;
            }
            return j;
        } catch (NumberFormatException unused) {
            g.a("unknown time format: " + str);
            return 0L;
        }
    }

    public static long b(String str) {
        return TimeUnit.SECONDS.toMillis(a(str));
    }

    public static String a(long j) {
        long j2 = j / 60;
        return String.format("%1$d:%2$02d:%3$02d", Long.valueOf(j2 / 60), Long.valueOf(j2 % 60), Long.valueOf(j % 60));
    }

    public static String b(long j) {
        return a(TimeUnit.MILLISECONDS.toSeconds(j));
    }

    public static int c(String str) {
        Matcher matcher = Pattern.compile("-?\\d+").matcher(d(str));
        if (matcher.find()) {
            return Integer.parseInt(matcher.group());
        }
        return 0;
    }

    public static String d(String str) {
        if (str == null || str.isEmpty()) {
            return "";
        }
        if (str.charAt(0) < 128 && str.charAt(str.length() - 1) < 128) {
            return str;
        }
        StringBuilder sb = new StringBuilder();
        for (int i = 0; i < str.length(); i++) {
            char cCharAt = str.charAt(i);
            if (cCharAt < 128) {
                sb.append(cCharAt);
            }
        }
        return sb.toString();
    }

    public static String a(String[] strArr, int i) {
        if (i == 0) {
            return "";
        }
        StringBuilder sb = new StringBuilder(strArr[0]);
        for (int i2 = 1; i2 < i; i2++) {
            sb.append(" ").append(strArr[i2]);
        }
        return sb.toString();
    }

    public static String a(int[] iArr) {
        return "[" + ((String) Arrays.stream(iArr).mapToObj(String::valueOf).collect(Collectors.joining(","))) + "]";
    }

    public static int a(int i, int i2) {
        return ((400 - i2) * 801) + i + 400 + 1;
    }
}
