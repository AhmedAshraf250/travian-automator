package traviaut.gui;

import javax.swing.Icon;
import javax.swing.ImageIcon;

/* JADX INFO: loaded from: traviaut.jar:traviaut/gui/f.class */
public enum f {
    CONF("conf.png");

    public final Icon b;

    f(String str) {
        this.b = new ImageIcon(ClassLoader.getSystemResource("traviaut/gui/res/" + str));
    }
}
