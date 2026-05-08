package traviaut.gui;

import java.awt.GridBagConstraints;
import java.awt.Insets;

/* JADX INFO: loaded from: traviaut.jar:traviaut/gui/c.class */
public final class c {
    public final GridBagConstraints a = new GridBagConstraints();

    public c(int i, int i2) {
        this.a.gridx = i;
        this.a.gridy = i2;
        this.a.weightx = 0.1d;
    }

    public final c a() {
        this.a.anchor = 13;
        return this;
    }

    public final c b() {
        this.a.anchor = 17;
        return this;
    }

    public final c a(int i) {
        this.a.fill = 1;
        return this;
    }

    public final c c() {
        this.a.fill = 2;
        return this;
    }

    public final c b(int i) {
        this.a.insets = new Insets(i, i, i, i);
        return this;
    }
}
