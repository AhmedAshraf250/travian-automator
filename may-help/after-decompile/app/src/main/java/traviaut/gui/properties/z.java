package traviaut.gui.properties;

import javax.swing.GroupLayout;
import javax.swing.JLabel;
import javax.swing.JTextField;

/* JADX INFO: loaded from: traviaut.jar:traviaut/gui/properties/z.class */
public final class z implements A {
    private final String a;
    private final JLabel b;
    private final JTextField c = new JTextField(40);

    public z(String str, String str2, String str3) {
        this.a = str;
        this.b = new JLabel(str2);
        this.c.setToolTipText(str3);
    }

    @Override // traviaut.gui.properties.A
    public final void a(GroupLayout.Group group) {
        group.addComponent(this.b);
    }

    @Override // traviaut.gui.properties.A
    public final void b(GroupLayout.Group group) {
        group.addComponent(this.c);
    }

    @Override // traviaut.gui.properties.A
    public final void a(Object obj) throws ReflectiveOperationException, IllegalArgumentException {
        this.c.setText((String) obj.getClass().getDeclaredField(this.a).get(obj));
    }

    @Override // traviaut.gui.properties.A
    public final void b(Object obj) throws ReflectiveOperationException, IllegalArgumentException {
        obj.getClass().getDeclaredField(this.a).set(obj, traviaut.m.d(this.c.getText().trim()));
    }
}
