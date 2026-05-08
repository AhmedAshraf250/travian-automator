package traviaut.gui.properties;

import java.awt.GridBagLayout;
import java.util.ArrayList;
import java.util.List;
import javax.swing.JCheckBox;
import javax.swing.JComponent;
import javax.swing.JLabel;
import javax.swing.JPanel;
import javax.swing.JScrollPane;
import javax.swing.JSpinner;
import javax.swing.SpinnerNumberModel;
import traviaut.xml.TAHero;
import traviaut.xml.TAHeroItem;

/* JADX INFO: loaded from: traviaut.jar:traviaut/gui/properties/k.class */
public final class k extends u {
    private final TAHero b;
    private final JCheckBox c = new JCheckBox("Sell items");
    private final List<JCheckBox> d = new ArrayList();
    private final List<JSpinner> e = new ArrayList();
    public boolean a;

    public k(TAHero tAHero) {
        this.b = tAHero;
        this.c.setSelected(tAHero.auctionsell);
    }

    @Override // traviaut.gui.properties.u
    public final String a() {
        return "items to sell";
    }

    @Override // traviaut.gui.properties.x
    public final void d() {
        this.b.auctionsell = this.c.isSelected();
        for (int i = 0; i < this.d.size(); i++) {
            TAHeroItem tAHeroItem = this.b.items.get(i);
            tAHeroItem.sell = this.d.get(i).isSelected();
            tAHeroItem.reserve = ((Number) this.e.get(i).getValue()).intValue();
        }
    }

    private static traviaut.gui.c a(int i, int i2) {
        traviaut.gui.c cVar = new traviaut.gui.c(i, i2);
        cVar.a.weightx = 0.0d;
        return cVar.b();
    }

    @Override // traviaut.gui.properties.u
    public final JComponent b() {
        JPanel jPanel = new JPanel(new GridBagLayout());
        this.c.setEnabled(this.a);
        jPanel.add(this.c, a(0, 0).a);
        jPanel.add(new JLabel("sell"), a(0, 1).b(5).a);
        jPanel.add(new JLabel("reserve"), a(1, 1).a);
        int i = 0 + 1 + 1;
        for (TAHeroItem tAHeroItem : this.b.items) {
            int i2 = i;
            i++;
            JCheckBox jCheckBox = new JCheckBox(traviaut.b.g.a.get(Integer.valueOf(tAHeroItem.id)), tAHeroItem.sell);
            jCheckBox.setEnabled(this.a);
            this.d.add(jCheckBox);
            jPanel.add(jCheckBox, a(0, i2).b(5).a);
            JSpinner jSpinner = new JSpinner(new SpinnerNumberModel(tAHeroItem.reserve, 0, 10000, 1));
            jSpinner.setEnabled(this.a);
            this.e.add(jSpinner);
            jPanel.add(jSpinner, a(1, i2).b(5).a);
        }
        return new JScrollPane(jPanel);
    }
}
