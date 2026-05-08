package traviaut.gui.properties;

import java.util.ArrayList;
import java.util.Iterator;
import java.util.List;
import javax.swing.BorderFactory;
import javax.swing.BoxLayout;
import javax.swing.JComponent;
import javax.swing.JPanel;

/* JADX INFO: loaded from: traviaut.jar:traviaut/gui/properties/y.class */
public final class y extends u {
    private final String a;
    private final List<u> b = new ArrayList();

    public y(String str) {
        this.a = str;
    }

    public final void a(u uVar) {
        this.b.add(uVar);
    }

    @Override // traviaut.gui.properties.u
    public final String a() {
        return this.a;
    }

    @Override // traviaut.gui.properties.u
    public final JComponent b() {
        JPanel jPanel = new JPanel();
        jPanel.setLayout(new BoxLayout(jPanel, 1));
        for (u uVar : this.b) {
            JComponent jComponentB = uVar.b();
            jComponentB.setBorder(BorderFactory.createTitledBorder(uVar.a()));
            jComponentB.setAlignmentX(0.0f);
            jPanel.add(jComponentB);
        }
        return jPanel;
    }

    @Override // traviaut.gui.properties.u, traviaut.gui.properties.x
    public final void c() {
        Iterator<u> it = this.b.iterator();
        while (it.hasNext()) {
            it.next().c();
        }
    }

    @Override // traviaut.gui.properties.x
    public final void d() {
        Iterator<u> it = this.b.iterator();
        while (it.hasNext()) {
            it.next().d();
        }
    }
}
