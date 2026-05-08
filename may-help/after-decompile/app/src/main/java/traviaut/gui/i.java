package traviaut.gui;

import java.awt.Color;
import java.awt.Component;
import java.util.List;
import javax.swing.JLabel;
import javax.swing.JList;
import javax.swing.ListCellRenderer;
import traviaut.b.s;

/* JADX INFO: loaded from: traviaut.jar:traviaut/gui/i.class */
final class i extends JLabel implements ListCellRenderer<JLabel> {
    private static final Color[] a = {new Color(0), new Color(7458816), new Color(16744448), new Color(2263842), new Color(15910656), new Color(16711680), new Color(11862179), new Color(8879)};
    private List<s.b> b;

    i() {
    }

    public final void a(List<s.b> list) {
        this.b = list;
    }

    public final /* synthetic */ Component getListCellRendererComponent(JList jList, Object obj, int i, boolean z, boolean z2) {
        int i2;
        JLabel jLabel = (JLabel) obj;
        if (z) {
            jLabel.setBackground(jList.getSelectionBackground());
        } else {
            jLabel.setBackground(jList.getBackground());
        }
        Color color = a[0];
        if (this.b != null && i < this.b.size() && (i2 = this.b.get(i).b) < 8) {
            color = a[i2];
        }
        jLabel.setForeground(color);
        jLabel.setFont(jList.getFont());
        return jLabel;
    }
}
