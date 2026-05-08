package traviaut.gui.properties;

import javax.swing.GroupLayout;
import javax.swing.JLabel;
import javax.swing.JSpinner;
import javax.swing.SpinnerNumberModel;

/* JADX INFO: loaded from: traviaut.jar:traviaut/gui/properties/v.class */
public final class v implements A {
    private final String a;
    private final JLabel b;
    private final JSpinner c;

    public v(String str, String str2, String str3, int i, int i2, int i3) {
        this.a = str;
        this.b = new JLabel(str2);
        this.c = new JSpinner(new SpinnerNumberModel(i, i, i2, i3));
        this.c.setToolTipText(str3);
    }

    public final void a() {
        this.c.setEnabled(false);
    }

    @Override // traviaut.gui.properties.A
    public final void a(GroupLayout.Group group) {
        group.addComponent(this.b);
    }

    @Override // traviaut.gui.properties.A
    public final void b(GroupLayout.Group group) {
        w.a(group, this.c);
    }

    @Override // traviaut.gui.properties.A
    public final void a(Object obj) throws ReflectiveOperationException, IllegalArgumentException {
        this.c.getModel().setValue(Integer.valueOf(obj.getClass().getDeclaredField(this.a).getInt(obj)));
    }

    @Override // traviaut.gui.properties.A
    public final void b(Object obj) throws ReflectiveOperationException, IllegalArgumentException {
        obj.getClass().getDeclaredField(this.a).set(obj, this.c.getValue());
    }
}
