package traviaut.gui.properties;

import javafx.beans.Observable;
import javafx.beans.binding.Bindings;
import javafx.beans.property.IntegerProperty;
import javafx.beans.property.ObjectProperty;
import javafx.beans.property.SimpleIntegerProperty;
import javafx.beans.property.SimpleObjectProperty;
import javafx.geometry.Insets;
import javafx.scene.Node;
import javafx.scene.control.CheckBox;
import javafx.scene.control.RadioButton;
import javafx.scene.control.TextField;
import javafx.scene.control.ToggleGroup;
import javafx.scene.layout.ColumnConstraints;
import javafx.scene.layout.GridPane;
import javafx.scene.layout.HBox;
import javafx.scene.layout.Priority;
import javafx.scene.layout.VBox;
import traviaut.Main;
import traviaut.b.m;

/* JADX INFO: loaded from: traviaut.jar:traviaut/gui/properties/f.class */
public final class f extends GridPane implements x {
    private final ToggleGroup a = new ToggleGroup();
    private final RadioButton b = new RadioButton("total amount");
    private final RadioButton c = new RadioButton("crop only");
    private final RadioButton d = new RadioButton("exact resources");
    private final TextField e = org.a.a.b();
    private final e f = new e();
    private final e g = new e();
    private final TextField h = org.a.a.b();
    private final TextField i = org.a.a.a("X coord:", false);
    private final TextField j = org.a.a.a("Y coord:", false);
    private final CheckBox k = new CheckBox("Send full merchants");
    private final IntegerProperty l = new SimpleIntegerProperty();
    private final ObjectProperty<traviaut.b.l> m = new SimpleObjectProperty(new traviaut.b.l());
    private final String n;

    public f(String str, int i, int i2) {
        this.n = str;
        ColumnConstraints columnConstraints = new ColumnConstraints();
        columnConstraints.setHgrow(Priority.SOMETIMES);
        getColumnConstraints().setAll(new ColumnConstraints[]{columnConstraints});
        setHgap(10.0d);
        setVgap(20.0d);
        setPadding(new Insets(10.0d));
        this.b.setToggleGroup(this.a);
        this.c.setToggleGroup(this.a);
        this.d.setToggleGroup(this.a);
        add(new VBox(10.0d, new Node[]{org.a.a.a("Resources amount:"), this.b, this.c, this.d}), 0, 0);
        VBox vBox = new VBox(20.0d, new Node[]{this.e, this.f});
        vBox.setFillWidth(false);
        add(vBox, 1, 0);
        this.g.setDisable(true);
        this.h.setDisable(true);
        add(new HBox(10.0d, new Node[]{org.a.a.a("Result:"), this.g, org.a.a.a("Total:"), this.h}), 0, 1, 2, 1);
        this.i.setText(String.valueOf(i));
        this.j.setText(String.valueOf(i2));
        add(new HBox(10.0d, new Node[]{org.a.a.a("X:"), this.i, org.a.a.a("Y:"), this.j, this.k}), 0, 2, 2, 1);
        this.b.setSelected(true);
        this.e.disableProperty().bind(this.d.selectedProperty());
        this.f.disableProperty().bind(this.b.selectedProperty().or(this.c.selectedProperty()));
        this.l.bind(Bindings.createIntegerBinding(() -> {
            return Integer.valueOf(org.a.a.a(this.e));
        }, new Observable[]{this.e.textProperty()}));
        this.m.bind(Bindings.createObjectBinding(() -> {
            traviaut.b.l lVarA = null;
            if (this.b.isSelected()) {
                lVarA = new traviaut.b.l(0);
            } else if (this.c.isSelected()) {
                lVarA = new traviaut.b.l(new int[]{0, 0, 0, this.l.get()});
            } else if (this.d.isSelected()) {
                lVarA = this.f.a();
            }
            return lVarA;
        }, new Observable[]{this.a.selectedToggleProperty(), this.l, this.f.a}));
        this.m.addListener(this.g);
        this.h.textProperty().bind(Bindings.when(this.a.selectedToggleProperty().isEqualTo(this.b)).then(this.l.asString()).otherwise(Bindings.createStringBinding(() -> {
            return Integer.toString(((traviaut.b.l) this.m.getValue()).i());
        }, new Observable[]{this.m})));
    }

    @Override // traviaut.gui.properties.x
    public final void c() {
    }

    @Override // traviaut.gui.properties.x
    public final void d() {
        int i = Integer.parseInt(this.i.getText());
        int i2 = Integer.parseInt(this.j.getText());
        traviaut.b.m mVar = new traviaut.b.m(m.b.EQ_REMAINING);
        if (this.a.getSelectedToggle() != this.b) {
            mVar.a((traviaut.b.l) this.m.get());
        } else {
            mVar.a(this.l.get());
        }
        if (this.k.isSelected()) {
            mVar.a();
        }
        traviaut.k.a(new traviaut.b.n(i, i2, mVar).d().a(Main.a.a(this.n, i, i2)), this.n);
    }
}
