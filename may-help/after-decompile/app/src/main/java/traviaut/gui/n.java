package traviaut.gui;

import java.awt.Color;
import java.awt.FlowLayout;
import java.awt.GridBagLayout;
import java.util.ArrayList;
import java.util.List;
import javax.swing.JButton;
import javax.swing.JLabel;
import javax.swing.JPanel;
import traviaut.gui.v;

/* JADX INFO: loaded from: traviaut.jar:traviaut/gui/n.class */
public final class n extends JPanel {
    public final u a;
    private final traviaut.e h;
    private final int[] b = {-1};
    private final List<v> c = new ArrayList();
    private final q d = new q();
    private final q e = new q();
    private final v.a f = new v.a();
    private final JLabel g = new JLabel();
    private final JLabel i = new JLabel();
    private final JLabel j = new JLabel();
    private final JLabel k = new JLabel();
    private final JPanel m = new JPanel(new FlowLayout());
    private final JButton l = new JButton("ok");

    public n(u uVar, traviaut.e eVar) {
        this.a = uVar;
        this.h = eVar;
        this.l.setEnabled(false);
        this.l.addActionListener(actionEvent -> {
            this.h.d.c();
            a();
            traviaut.k.a(this.h);
        });
        setLayout(new GridBagLayout());
        this.d.a(new Color(0, 0, 192));
        this.e.a(Color.GRAY);
    }

    public final void a(s sVar) {
        this.c.stream().forEach(vVar -> {
            vVar.a(sVar);
        });
        this.d.a(sVar);
        this.f.a(sVar, traviaut.b.s.a);
    }

    /* JADX WARN: Removed duplicated region for block: B:25:0x010a  */
    /*
        Code decompiled incorrectly, please refer to instructions dump.
        To view partially-correct code enable 'Show inconsistent code' option in preferences
    */
    public final void a() {
        /*
            Method dump skipped, instruction units count: 848
            To view this dump change 'Code comments level' option to 'DEBUG'
        */
        throw new UnsupportedOperationException("Method not decompiled: traviaut.gui.n.a():void");
    }
}
