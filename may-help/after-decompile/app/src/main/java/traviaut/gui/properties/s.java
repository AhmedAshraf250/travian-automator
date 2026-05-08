package traviaut.gui.properties;

import javax.swing.GroupLayout;
import javax.swing.JCheckBox;

/* JADX INFO: loaded from: traviaut.jar:traviaut/gui/properties/s.class */
public final class s implements A {
    private final String a;
    private final JCheckBox b;

    public s(String str, String str2, String str3) {
        this.a = str;
        this.b = new JCheckBox(str2);
        this.b.setToolTipText(str3);
    }

    public final void a() {
        this.b.setEnabled(false);
    }

    @Override // traviaut.gui.properties.A
    public final void a(GroupLayout.Group group) {
    }

    @Override // traviaut.gui.properties.A
    public final void b(GroupLayout.Group group) {
        group.addComponent(this.b);
    }

    @Override // traviaut.gui.properties.A
    public final void a(Object obj) throws ReflectiveOperationException, IllegalArgumentException {
        this.b.setSelected(obj.getClass().getDeclaredField(this.a).getBoolean(obj));
    }

    @Override // traviaut.gui.properties.A
    public final void b(Object obj) throws ReflectiveOperationException, IllegalArgumentException {
        obj.getClass().getDeclaredField(this.a).setBoolean(obj, this.b.isSelected());
    }
}
