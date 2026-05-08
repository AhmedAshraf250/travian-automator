package traviaut;

import java.io.File;
import java.io.FileInputStream;
import java.io.IOException;
import java.nio.file.Files;
import java.nio.file.StandardCopyOption;
import javax.xml.bind.JAXBContext;
import javax.xml.bind.JAXBException;
import javax.xml.bind.Marshaller;
import traviaut.xml.TAAcc;
import traviaut.xml.TAData;
import traviaut.xml.TAGlobalSets;
import traviaut.xml.TAGui;
import traviaut.xml.TAVillage;

/* JADX INFO: loaded from: traviaut.jar:traviaut/f.class */
public final class f {
    private static final f a = new f();
    private final TAData b;
    private final File c = new File(Main.b);
    private final File d = new File(Main.b + ".tmp");
    private final Marshaller e;
    private static String f;

    private f() {
        Marshaller marshaller = null;
        TAData tAData = new TAData();
        try {
            JAXBContext jAXBContextNewInstance = JAXBContext.newInstance(new Class[]{TAData.class});
            Marshaller marshallerCreateMarshaller = jAXBContextNewInstance.createMarshaller();
            marshaller = marshallerCreateMarshaller;
            marshallerCreateMarshaller.setProperty("jaxb.formatted.output", true);
            if (this.c.isFile() && this.c.canRead()) {
                FileInputStream fileInputStream = new FileInputStream(this.c);
                try {
                    try {
                        tAData = (TAData) jAXBContextNewInstance.createUnmarshaller().unmarshal(fileInputStream);
                        fileInputStream.close();
                    } finally {
                    }
                } finally {
                }
            }
        } catch (Exception e) {
            g.a("failed to load config", e);
        }
        this.e = marshaller;
        this.b = tAData;
        this.b.clean();
        f = this.b.getSets().userID;
        Main.d.set(f.isEmpty() ? 0 : 1);
        g.a("config loaded");
    }

    public static void a() {
        try {
            if (!c().userID.equals(f)) {
                f = c().userID;
                Main.d.set(f.isEmpty() ? 0 : 1);
            }
            synchronized (a) {
                a.e.marshal(a.b, a.d);
                Files.move(a.d.toPath(), a.c.toPath(), StandardCopyOption.ATOMIC_MOVE, StandardCopyOption.REPLACE_EXISTING);
            }
        } catch (JAXBException | IOException e) {
            g.a("failed to save config", e);
        }
    }

    public static TAGui b() {
        return a.b.gui;
    }

    public static TAGlobalSets c() {
        return a.b.getSets();
    }

    public static TAAcc a(String str) {
        return a.b.accounts.getAccount(str);
    }

    public static TAVillage a(e eVar, int i) {
        return a.b.servers.getServer(eVar.a).getVillage(i, eVar.b());
    }
}
