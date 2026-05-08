package traviaut;

import java.time.LocalDateTime;
import java.time.format.DateTimeFormatter;
import java.time.format.FormatStyle;
import java.util.Iterator;
import java.util.LinkedList;
import java.util.List;

/* JADX INFO: loaded from: traviaut.jar:traviaut/g.class */
public abstract class g {
    private static final List<h> b = new LinkedList();
    private static DateTimeFormatter c = DateTimeFormatter.ofLocalizedDateTime(FormatStyle.MEDIUM);
    public static final String a = System.getProperty("java.version");

    public static void a() {
        a("TraviAut - Travian Automator");
        a("Version: 20161021.0-tap");
        a("Copyright © 2007-2016 Ondrej Preclik");
        a("Java version: " + a);
        a("TO AVOID CAPTCHA FILL IN YOUR USER_AGENT IN SETTINGS");
        a("if something doesn't work, contact me at ondrej.preclik at gmail dot com (include this log)");
    }

    private static void b(String str) {
        Iterator<h> it = b.iterator();
        while (it.hasNext()) {
            it.next().addLog(str);
        }
    }

    public static void a(String str) {
        synchronized (c) {
            b(c.format(LocalDateTime.now()) + " " + str);
        }
    }

    public static void a(String str, Throwable th) {
        a(str + ":");
        a(th.toString());
        for (StackTraceElement stackTraceElement : th.getStackTrace()) {
            b("\t" + stackTraceElement.toString());
        }
    }

    public static void a(h hVar) {
        b.add(hVar);
    }
}
