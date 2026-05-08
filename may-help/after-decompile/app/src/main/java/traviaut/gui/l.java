package traviaut.gui;

import java.awt.Color;
import java.awt.event.ActionListener;
import java.util.ArrayList;
import java.util.List;
import javax.swing.JButton;
import javax.swing.JCheckBoxMenuItem;
import javax.swing.JPopupMenu;
import javax.swing.event.PopupMenuEvent;
import javax.swing.event.PopupMenuListener;

/* JADX INFO: loaded from: traviaut.jar:traviaut/gui/l.class */
public final class l {
    private final JButton a;
    private final JPopupMenu b = new JPopupMenu();
    private final List<k> c = new ArrayList();
    private final ActionListener d = actionEvent -> {
        this.b.setVisible(true);
    };

    public l(String str, String str2, boolean z, final m mVar) {
        this.a = new JButton(str);
        this.a.setToolTipText(str2);
        a(z);
        this.a.addActionListener(actionEvent -> {
            this.b.removeAll();
            this.c.clear();
            this.c.addAll(mVar.a());
            for (k kVar : this.c) {
                JCheckBoxMenuItem jCheckBoxMenuItem = new JCheckBoxMenuItem(kVar.b, kVar.c);
                jCheckBoxMenuItem.addActionListener(this.d);
                this.b.add(jCheckBoxMenuItem);
            }
            this.b.show(this.a, 0, this.a.getHeight());
        });
        this.b.addPopupMenuListener(new PopupMenuListener() { // from class: traviaut.gui.l.1
            public final void popupMenuWillBecomeVisible(PopupMenuEvent popupMenuEvent) {
            }

            public final void popupMenuWillBecomeInvisible(PopupMenuEvent popupMenuEvent) {
            }

            public final void popupMenuCanceled(PopupMenuEvent popupMenuEvent) {
                for (int i = 0; i < l.this.c.size(); i++) {
                    ((k) l.this.c.get(i)).c = l.this.b.getComponent(i).isSelected();
                }
                mVar.a(l.this.c);
                l.this.a(((k) l.this.c.get(0)).c);
                traviaut.f.a();
            }
        });
    }

    /* JADX INFO: Access modifiers changed from: private */
    public void a(boolean z) {
        this.a.setBackground(z ? Color.GREEN : null);
    }

    public final JButton a() {
        return this.a;
    }
}
