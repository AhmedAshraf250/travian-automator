package traviaut;

import java.io.File;
import java.io.FileNotFoundException;
import java.io.FileWriter;
import java.io.IOException;
import java.lang.management.ManagementFactory;
import java.util.Scanner;

/* JADX INFO: loaded from: traviaut.jar:traviaut/i.class */
public final class i {
    private static final String a = Main.a("lock");

    private static String c() {
        return ManagementFactory.getRuntimeMXBean().getName();
    }

    public static void a() {
        try {
            FileWriter fileWriter = new FileWriter(new File(a));
            try {
                fileWriter.write(c());
                fileWriter.close();
            } finally {
            }
        } catch (IOException unused) {
            g.a("failed to prot");
        }
    }

    public static void b() {
        try {
            if (new Scanner(new File(a)).next().equals(c())) {
                return;
            }
            System.exit(-1);
        } catch (FileNotFoundException unused) {
            System.exit(-2);
        }
    }
}
