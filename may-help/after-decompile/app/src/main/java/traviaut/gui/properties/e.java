package traviaut.gui.properties;

import javafx.beans.binding.Bindings;
import javafx.beans.property.ObjectProperty;
import javafx.beans.property.SimpleObjectProperty;
import javafx.beans.property.StringProperty;
import javafx.beans.value.ChangeListener;
import javafx.beans.value.ObservableValue;
import javafx.scene.control.TextField;
import javafx.scene.layout.HBox;

/* JADX INFO: loaded from: traviaut.jar:traviaut/gui/properties/e.class */
public final class e extends HBox implements ChangeListener<traviaut.b.l> {
    private final TextField[] b = new TextField[4];
    public final ObjectProperty<traviaut.b.l> a = new SimpleObjectProperty();

    public e() {
        setSpacing(10.0d);
        StringProperty[] stringPropertyArr = new StringProperty[4];
        for (int i = 0; i < 4; i++) {
            this.b[i] = org.a.a.b();
            stringPropertyArr[i] = this.b[i].textProperty();
        }
        this.a.bind(Bindings.createObjectBinding(this::a, stringPropertyArr));
        getChildren().addAll(this.b);
        this.a.addListener((observableValue, lVar, lVar2) -> {
        });
    }

    public final traviaut.b.l a() {
        int[] iArr = new int[4];
        for (int i = 0; i < 4; i++) {
            iArr[i] = org.a.a.a(this.b[i]);
        }
        return new traviaut.b.l(iArr);
    }

    public final /* synthetic */ void changed(ObservableValue observableValue, Object obj, Object obj2) {
        traviaut.b.l lVar = (traviaut.b.l) obj2;
        for (int i = 0; i < 4; i++) {
            if (!this.b[i].getText().equals(lVar.d(i))) {
                this.b[i].setText(lVar.d(i));
            }
        }
    }
}
