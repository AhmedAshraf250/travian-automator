package traviaut.gui;

import java.awt.Color;
import java.awt.GridLayout;
import javax.swing.BorderFactory;
import javax.swing.JLabel;
import javax.swing.JPanel;

/* JADX INFO: loaded from: traviaut.jar:traviaut/gui/q.class */
public final class q extends JPanel {
    private final JLabel[] a = new JLabel[4];

    public q() {
        setLayout(new GridLayout());
        for (int i = 0; i < 4; i++) {
            JLabel jLabel = new JLabel();
            jLabel.setBorder(BorderFactory.createEmptyBorder(0, 10, 0, 10));
            jLabel.setHorizontalAlignment(4);
            add(jLabel);
            this.a[i] = jLabel;
        }
    }

    public final JLabel a() {
        return this.a[3];
    }

    public final void a(Color color) {
        JLabel[] jLabelArr = this.a;
        for (int i = 0; i < 4; i++) {
            jLabelArr[i].setForeground(color);
        }
    }

    public final void a(traviaut.b.l lVar) {
        for (int i = 0; i < 4; i++) {
            this.a[i].setText(lVar.d(i));
        }
    }

    public final void a(s sVar, traviaut.d.c cVar) {
        for (int i = 0; i < 4; i++) {
            sVar.a(this.a[i], cVar.a(i)).actionPerformed(null);
        }
    }

    public final void a(s sVar) {
        for (int i = 0; i < 4; i++) {
            sVar.a(this.a[i]);
        }
    }
}
