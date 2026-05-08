package traviaut.gui.properties;

import java.awt.Component;
import java.awt.Dimension;
import java.awt.GridBagLayout;
import java.util.ArrayList;
import java.util.List;
import java.util.Vector;
import javax.swing.JComboBox;
import javax.swing.JComponent;
import javax.swing.JLabel;
import javax.swing.JList;
import javax.swing.JPanel;
import javax.swing.JScrollPane;
import javax.swing.JTextField;
import javax.swing.ListCellRenderer;
import traviaut.xml.TABuildLayout;
import traviaut.xml.TABuildPlace;

/* JADX INFO: loaded from: traviaut.jar:traviaut/gui/properties/n.class */
public final class n extends u {
    private final TABuildLayout a;
    private final Vector<Integer> b = new Vector<>();
    private final List<JComboBox<Integer>> c = new ArrayList();
    private final List<JTextField> d = new ArrayList();
    private final List<JTextField> e = new ArrayList();

    public n(TABuildLayout tABuildLayout) {
        this.a = tABuildLayout;
        this.b.add(0);
        for (int i = 5; i < 42; i++) {
            if (traviaut.b.a.b()[i] != null && (i <= 30 || i >= 34)) {
                this.b.add(Integer.valueOf(i));
            }
        }
        this.b.add(100);
    }

    @Override // traviaut.gui.properties.u
    public final String a() {
        return "Layout";
    }

    @Override // traviaut.gui.properties.u, traviaut.gui.properties.x
    public final void c() {
    }

    @Override // traviaut.gui.properties.x
    public final void d() {
        for (int i = 0; i < 22; i++) {
            TABuildPlace place = this.a.getPlace(i + 19);
            int iIntValue = ((Integer) this.c.get(i).getSelectedItem()).intValue();
            String text = this.d.get(i).getText();
            int i2 = -1;
            if (text.equals("max")) {
                place.priority = Integer.parseInt(this.e.get(i).getText());
                place.gid = iIntValue;
                place.maxlvl = i2;
            } else {
                try {
                    i2 = Integer.parseInt(text);
                    try {
                        place.priority = Integer.parseInt(this.e.get(i).getText());
                        place.gid = iIntValue;
                        place.maxlvl = i2;
                    } catch (NumberFormatException unused) {
                        traviaut.g.a("WARNING: unknown number " + text);
                    }
                } catch (NumberFormatException unused2) {
                    traviaut.g.a("WARNING: unknown number " + text);
                }
            }
        }
    }

    /* JADX INFO: loaded from: traviaut.jar:traviaut/gui/properties/n$a.class */
    static class a extends JLabel implements ListCellRenderer<Integer> {
        private a() {
        }

        /* synthetic */ a(byte b) {
            this();
        }

        public final /* synthetic */ Component getListCellRendererComponent(JList jList, Object obj, int i, boolean z, boolean z2) {
            setText(n.a((Integer) obj));
            return this;
        }
    }

    @Override // traviaut.gui.properties.u
    public final JComponent b() {
        JPanel jPanel = new JPanel(new GridBagLayout());
        jPanel.add(new JLabel("place ID"), new traviaut.gui.c(0, 0).a);
        jPanel.add(new JLabel("building"), new traviaut.gui.c(1, 0).a);
        jPanel.add(new JLabel("max level"), new traviaut.gui.c(2, 0).a);
        jPanel.add(new JLabel("priority"), new traviaut.gui.c(3, 0).a);
        int i = 0 + 1;
        for (int i2 = 0; i2 < 22; i2++) {
            int i3 = i;
            i++;
            int i4 = i2;
            TABuildPlace place = this.a.getPlace(i4 + 19);
            jPanel.add(new JLabel(Integer.toString(place.id)), new traviaut.gui.c(0, i3).b(5).a);
            JComboBox<Integer> jComboBox = new JComboBox<>(this.b);
            jComboBox.setRenderer(new a((byte) 0));
            jComboBox.setEditable(false);
            jComboBox.setSelectedItem(Integer.valueOf(place.gid));
            if (place.id == 26 || place.id > 38) {
                jComboBox.setEnabled(false);
            }
            jPanel.add(jComboBox, new traviaut.gui.c(1, i3).b(5).a);
            this.c.add(i4, jComboBox);
            JTextField jTextField = new JTextField(place.maxlvl == -1 ? "max" : Integer.toString(place.maxlvl));
            jTextField.setColumns(10);
            jTextField.setMinimumSize(new Dimension(100, 20));
            jPanel.add(jTextField, new traviaut.gui.c(2, i3).b(5).a);
            this.d.add(i4, jTextField);
            JTextField jTextField2 = new JTextField(Integer.toString(place.priority));
            jTextField2.setColumns(10);
            jTextField2.setMinimumSize(new Dimension(100, 20));
            jPanel.add(jTextField2, new traviaut.gui.c(3, i3).b(5).a);
            this.e.add(i4, jTextField2);
        }
        return new JScrollPane(jPanel);
    }

    static /* synthetic */ String a(Integer num) {
        switch (num.intValue()) {
            case 0:
                return "<EMPTY>";
            case 13:
                return "T4 smithy, T3 armory";
            case 100:
                return "wall";
            default:
                return traviaut.b.a.b()[num.intValue()].b;
        }
    }
}
