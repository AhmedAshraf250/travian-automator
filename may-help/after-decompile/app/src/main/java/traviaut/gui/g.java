package traviaut.gui;

import java.awt.Color;
import javax.swing.JButton;
import traviaut.Main;

/* JADX INFO: loaded from: traviaut.jar:traviaut/gui/g.class */
public final class g extends JButton {
    private final Color a = new Color(16766720);

    public g() {
        addActionListener(actionEvent -> {
            h.a("http://traviaut.com");
        });
    }

    public final void a() {
        String str = "";
        Color color = null;
        String str2 = "TA Plus";
        switch (Main.c()) {
            case 0:
                str = "color=\"#FF0000\"";
                str2 = "TA Free - buy TA+ licence now";
                color = Color.DARK_GRAY;
                break;
            case 1:
                str2 = "TA Plus";
                break;
            case 2:
                color = this.a;
                str2 = "TA Gold";
                break;
        }
        String str3 = Main.e;
        if (org.a.f.e()) {
            str3 = str3 + " no connection to the license server";
        }
        if (!str3.isEmpty()) {
            str = "color=\"#FF0000\"";
            str2 = str2 + " - " + str3;
        }
        setText("<html><a " + str + " href=\"http://traviaut.com\">" + str2 + "</a></html>");
        setBackground(color);
    }
}
