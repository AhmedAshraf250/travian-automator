package traviaut.gui.properties;

import javafx.geometry.HPos;
import javafx.geometry.Pos;
import javafx.scene.control.Label;
import javafx.scene.layout.GridPane;
import traviaut.xml.TATroopsTraining;

/* JADX INFO: loaded from: traviaut.jar:traviaut/gui/properties/h.class */
public final class h extends GridPane implements x {
    private static final String[] a = {"Troops", "Horses", "Scouts", "Catapults"};
    private final g[] b = new g[4];

    @Override // traviaut.gui.properties.x
    public final void c() {
        g[] gVarArr = this.b;
        for (int i = 0; i < 4; i++) {
            gVarArr[i].c();
        }
    }

    @Override // traviaut.gui.properties.x
    public final void d() {
        g[] gVarArr = this.b;
        for (int i = 0; i < 4; i++) {
            gVarArr[i].d();
        }
    }

    public h(TATroopsTraining tATroopsTraining) {
        for (int i = 0; i < 4; i++) {
            this.b[i] = new g(tATroopsTraining.queue[i]);
        }
        setAlignment(Pos.CENTER);
        setHgap(10.0d);
        setVgap(10.0d);
        Label label = new Label("Amount for training");
        add(label, 1, 0);
        setHalignment(label, HPos.CENTER);
        add(new Label("Allow fewer"), 2, 0);
        Label label2 = new Label("Minimal Queue Length in minutes");
        add(label2, 3, 0);
        setHalignment(label2, HPos.CENTER);
        for (int i2 = 0; i2 < 4; i2++) {
            String str = a[i2] + " :";
            int i3 = i2 + 1;
            g gVar = this.b[i2];
            Label label3 = new Label(str);
            add(label3, 0, i3);
            setHalignment(label3, HPos.RIGHT);
            add(gVar.a, 1, i3);
            add(gVar.b, 2, i3);
            GridPane.setHalignment(gVar.b, HPos.CENTER);
            add(gVar.c, 3, i3);
        }
    }
}
