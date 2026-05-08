package traviaut;

import java.awt.EventQueue;
import java.io.File;
import java.io.FileOutputStream;
import java.io.IOException;
import java.io.OutputStreamWriter;
import java.io.PrintStream;
import java.lang.reflect.InvocationTargetException;
import java.net.CookieHandler;
import java.util.concurrent.atomic.AtomicInteger;
import traviaut.gui.u;

/* JADX INFO: loaded from: traviaut.jar:traviaut/Main.class */
public class Main {
    public static final c a = new c();
    private static final String f = System.getProperties().getProperty("file.separator");
    private static final String g = System.getProperties().getProperty("user.home") + f + ".traviaut";
    public static final String b = a("settings.xml");
    public static final String c = a("servers.cfg");
    public static final AtomicInteger d = new AtomicInteger();
    public static String e = "";

    public static String a(String str) {
        return g + f + str;
    }

    public static boolean a() {
        return d.get() > 0;
    }

    public static boolean b() {
        return d.get() > 1;
    }

    public static int c() {
        return d.get();
    }

    private static boolean a(File file) throws IOException {
        if (!file.createNewFile()) {
            g.a("failed to create " + file.getAbsolutePath() + " exiting");
            return false;
        }
        OutputStreamWriter outputStreamWriter = new OutputStreamWriter(new FileOutputStream(file));
        Throwable th = null;
        try {
            outputStreamWriter.write("# server login password\n# http://s1.travian.com killer coolpass\n# ! server ! login ! password\n# where ! is any character except h and #");
            outputStreamWriter.close();
            return true;
        } catch (Throwable th2) {
            if (0 != 0) {
                try {
                    outputStreamWriter.close();
                } catch (Throwable th3) {
                    th.addSuppressed(th3);
                }
            } else {
                outputStreamWriter.close();
            }
            throw th2;
        }
    }

    private static boolean d() {
        try {
            File file = new File(g);
            if (!file.exists() && !file.mkdir()) {
                g.a("failed to create " + g);
                return false;
            }
            if (!file.isDirectory()) {
                g.a(g + " is not directory");
                return false;
            }
            if (!file.canRead()) {
                g.a("failed to read " + g);
                return false;
            }
            if (!file.canWrite()) {
                g.a("failed to write " + g);
                return false;
            }
            File file2 = new File(c);
            if (file2.exists()) {
                return true;
            }
            return a(file2);
        } catch (Exception e2) {
            g.a("failed to check settings", e2);
            return false;
        }
    }

    public static void main(String[] strArr) {
        CookieHandler.setDefault(null);
        PrintStream printStream = System.out;
        printStream.getClass();
        g.a(printStream::println);
        k.a();
        try {
            EventQueue.invokeAndWait(() -> {
                new u().setVisible(true);
            });
        } catch (InterruptedException | InvocationTargetException e2) {
            g.a("failed to create GUI", e2);
        }
        i.a();
        g.a();
        if (d()) {
            Thread thread = new Thread(k.a(), "requester");
            thread.setDaemon(true);
            thread.start();
        }
    }
}
