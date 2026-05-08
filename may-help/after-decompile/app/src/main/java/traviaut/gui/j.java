package traviaut.gui;

import java.util.ArrayList;
import java.util.Iterator;
import java.util.List;
import javax.swing.AbstractListModel;
import javax.swing.JLabel;
import traviaut.b.s;

/* JADX INFO: loaded from: traviaut.jar:traviaut/gui/j.class */
public final class j extends AbstractListModel<JLabel> {
    private List<s.b> a = new ArrayList();
    private final List<JLabel> b = new ArrayList();

    /* JADX INFO: loaded from: traviaut.jar:traviaut/gui/j$a.class */
    class a extends JLabel {
        private final int a;

        a(int i) {
            this.a = i;
        }

        public final void setText(String str) {
            super.setText(str);
            j.this.fireContentsChanged(this, this.a, this.a);
        }
    }

    public final int getSize() {
        int size = this.a.size();
        if (size == 0) {
            return 1;
        }
        return size;
    }

    public final void a(List<s.b> list, s sVar) {
        this.a = list;
        Iterator<JLabel> it = this.b.iterator();
        while (it.hasNext()) {
            sVar.a(it.next());
        }
        this.b.clear();
        for (int i = 0; i < list.size(); i++) {
            a aVar = new a(i);
            sVar.a(aVar, list.get(i).a);
            this.b.add(aVar);
        }
        fireContentsChanged(this, 0, list.size());
    }

    public final /* synthetic */ Object getElementAt(int i) {
        return this.b.size() <= i ? new JLabel() : this.b.get(i);
    }
}
