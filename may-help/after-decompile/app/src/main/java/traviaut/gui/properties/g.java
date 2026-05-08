package traviaut.gui.properties;

import javafx.geometry.Pos;
import javafx.scene.control.CheckBox;
import javafx.scene.control.Spinner;
import traviaut.xml.TATroopsQueue;

/* JADX INFO: loaded from: traviaut.jar:traviaut/gui/properties/g.class */
public class g implements x {
    private final TATroopsQueue d;
    public final Spinner<Integer> a = new Spinner<>(1, 10000, 1);
    public final CheckBox b = new CheckBox();
    public final Spinner<Integer> c = new Spinner<>(1, 10000, 1);

    public g(TATroopsQueue tATroopsQueue) {
        this.d = tATroopsQueue;
        this.a.setEditable(true);
        this.a.getEditor().setAlignment(Pos.CENTER_RIGHT);
        org.a.a.a(this.a);
        this.c.setEditable(true);
        this.c.getEditor().setAlignment(Pos.CENTER_RIGHT);
        org.a.a.a(this.c);
    }

    @Override // traviaut.gui.properties.x
    public final void c() {
        this.a.getValueFactory().setValue(Integer.valueOf(this.d.amount));
        this.b.setSelected(this.d.allowfewer);
        this.c.getValueFactory().setValue(Integer.valueOf(this.d.queue));
    }

    @Override // traviaut.gui.properties.x
    public final void d() {
        this.d.amount = ((Integer) this.a.getValue()).intValue();
        this.d.allowfewer = this.b.isSelected();
        this.d.queue = ((Integer) this.c.getValue()).intValue();
    }
}
