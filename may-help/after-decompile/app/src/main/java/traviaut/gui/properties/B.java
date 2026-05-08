package traviaut.gui.properties;

import java.awt.FlowLayout;
import java.util.ArrayList;
import java.util.List;
import javax.swing.JComponent;
import javax.swing.JLabel;
import javax.swing.JPanel;
import javax.swing.JSpinner;
import javax.swing.SpinnerNumberModel;

/* JADX INFO: loaded from: traviaut.jar:traviaut/gui/properties/B.class */
public final class B extends u {
    private final int[] a;
    private final List<JSpinner> b = new ArrayList();

    public B(int[] iArr) {
        this.a = iArr;
    }

    @Override // traviaut.gui.properties.u
    public final String a() {
        return "resource priorities";
    }

    @Override // traviaut.gui.properties.u
    public final JComponent b() {
        JPanel jPanel = new JPanel();
        jPanel.setLayout(new FlowLayout());
        jPanel.add(new JLabel("resource priorities:"));
        for (int i = 0; i < 4; i++) {
            jPanel.add(this.b.get(i));
        }
        jPanel.add(new JLabel("negative crop:"));
        jPanel.add(this.b.get(4));
        return jPanel;
    }

    @Override // traviaut.gui.properties.u, traviaut.gui.properties.x
    public final void c() {
        for (int i = 0; i < this.a.length; i++) {
            this.b.add(new JSpinner(new SpinnerNumberModel(this.a[i], 0, 100, 1)));
        }
    }

    @Override // traviaut.gui.properties.x
    public final void d() {
        for (int i = 0; i < this.a.length; i++) {
            this.a[i] = ((Integer) this.b.get(i).getValue()).intValue();
        }
    }
}
