package traviaut.gui.properties;

import java.awt.Component;
import java.util.Iterator;
import java.util.LinkedList;
import java.util.List;
import javax.swing.GroupLayout;
import javax.swing.JComponent;
import javax.swing.JPanel;

/* JADX INFO: loaded from: traviaut.jar:traviaut/gui/properties/w.class */
public class w extends u {
    protected final List<A> a = new LinkedList();
    private final Object b;

    public w(Object obj) {
        this.b = obj;
    }

    public static void a(GroupLayout.Group group, Component component) {
        group.addComponent(component, -2, -1, -2);
    }

    private GroupLayout.SequentialGroup a(GroupLayout groupLayout) {
        GroupLayout.Group groupCreateParallelGroup = groupLayout.createParallelGroup(GroupLayout.Alignment.TRAILING);
        Iterator<A> it = this.a.iterator();
        while (it.hasNext()) {
            it.next().a(groupCreateParallelGroup);
        }
        GroupLayout.Group groupCreateParallelGroup2 = groupLayout.createParallelGroup();
        Iterator<A> it2 = this.a.iterator();
        while (it2.hasNext()) {
            it2.next().b(groupCreateParallelGroup2);
        }
        return groupLayout.createSequentialGroup().addGroup(groupCreateParallelGroup).addGroup(groupCreateParallelGroup2);
    }

    private GroupLayout.Group b(GroupLayout groupLayout) {
        GroupLayout.SequentialGroup sequentialGroupCreateSequentialGroup = groupLayout.createSequentialGroup();
        for (A a : this.a) {
            GroupLayout.ParallelGroup parallelGroupCreateParallelGroup = groupLayout.createParallelGroup(GroupLayout.Alignment.BASELINE);
            a.a((GroupLayout.Group) parallelGroupCreateParallelGroup);
            a.b((GroupLayout.Group) parallelGroupCreateParallelGroup);
            sequentialGroupCreateSequentialGroup.addGroup(parallelGroupCreateParallelGroup);
        }
        return sequentialGroupCreateSequentialGroup;
    }

    @Override // traviaut.gui.properties.u
    public final JComponent b() {
        JPanel jPanel = new JPanel();
        GroupLayout groupLayout = new GroupLayout(jPanel);
        jPanel.setLayout(groupLayout);
        groupLayout.setAutoCreateGaps(true);
        groupLayout.setAutoCreateContainerGaps(true);
        groupLayout.setHorizontalGroup(a(groupLayout));
        groupLayout.setVerticalGroup(b(groupLayout));
        return jPanel;
    }

    @Override // traviaut.gui.properties.u, traviaut.gui.properties.x
    public final void c() {
        try {
            Iterator<A> it = this.a.iterator();
            while (it.hasNext()) {
                it.next().a(this.b);
            }
        } catch (IllegalArgumentException | ReflectiveOperationException e) {
            traviaut.g.a("failed to save", e);
        }
    }

    @Override // traviaut.gui.properties.x
    public void d() {
        try {
            Iterator<A> it = this.a.iterator();
            while (it.hasNext()) {
                it.next().b(this.b);
            }
        } catch (IllegalArgumentException | ReflectiveOperationException e) {
            traviaut.g.a("failed to save", e);
        }
    }
}
