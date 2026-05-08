package traviaut.gui.properties;

import javax.swing.GroupLayout;
import javax.swing.JComboBox;
import javax.swing.JLabel;

/* JADX INFO: loaded from: traviaut.jar:traviaut/gui/properties/t.class */
public final class t<E> implements A {
    private final String a;
    private final JLabel b;
    private final JComboBox<E> c;

    public t(String str, String str2, E[] eArr) {
        this.a = str;
        this.b = new JLabel(str2);
        this.c = new JComboBox<>(eArr);
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
        this.c.getModel().setSelectedItem(obj.getClass().getDeclaredField(this.a).get(obj));
    }

    @Override // traviaut.gui.properties.A
    public final void b(Object obj) throws ReflectiveOperationException, IllegalArgumentException {
        obj.getClass().getDeclaredField(this.a).set(obj, this.c.getSelectedItem());
    }
}
