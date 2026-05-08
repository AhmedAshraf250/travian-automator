package traviaut.gui;

import java.awt.Desktop;
import java.awt.event.MouseAdapter;
import java.awt.event.MouseEvent;
import java.io.IOException;
import java.net.URI;
import java.net.URISyntaxException;
import javax.swing.JLabel;

/* JADX INFO: loaded from: traviaut.jar:traviaut/gui/h.class */
public final class h extends JLabel {
    private final String a;

    public h(String str) {
        this(str, str, "");
    }

    private h(String str, String str2, String str3) {
        this.a = str;
        setText("<html><a " + (str3.isEmpty() ? str3 : "color=#\"" + str3 + "\"") + " href=\"" + str + "\">" + str2 + "</a></html>");
        addMouseListener(new MouseAdapter() { // from class: traviaut.gui.h.1
            public final void mouseClicked(MouseEvent mouseEvent) {
                h.a(h.this.a);
            }
        });
    }

    public static void a(String str) {
        try {
            if (Desktop.isDesktopSupported()) {
                Desktop.getDesktop().browse(new URI(str));
            }
        } catch (IOException | NoClassDefFoundError | URISyntaxException unused) {
        }
    }
}
