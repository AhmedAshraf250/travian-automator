package traviaut.gui;

import java.awt.event.ActionEvent;
import java.awt.event.ActionListener;
import java.util.HashMap;
import java.util.Map;
import java.util.concurrent.TimeUnit;
import javax.swing.JLabel;
import javax.swing.Timer;

/* JADX INFO: loaded from: traviaut.jar:traviaut/gui/r.class */
public final class r implements ActionListener {
    private final int a;
    private final Map<JLabel, traviaut.d.a> b;
    private final Timer c;

    public r() {
        this((int) TimeUnit.SECONDS.toMillis(1L));
    }

    public r(int i) {
        this.b = new HashMap();
        this.a = i;
        this.c = new Timer(i, this);
        this.c.start();
    }

    public final void a(JLabel jLabel, traviaut.d.a aVar) {
        this.b.put(jLabel, aVar);
    }

    public final void a(JLabel jLabel, s sVar) {
        this.b.remove(jLabel);
        jLabel.setText("");
        if (this.b.isEmpty()) {
            sVar.a(this.a);
            this.c.stop();
        }
    }

    public final void actionPerformed(ActionEvent actionEvent) {
        long jCurrentTimeMillis = System.currentTimeMillis();
        for (Map.Entry<JLabel, traviaut.d.a> entry : this.b.entrySet()) {
            entry.getKey().setText(entry.getValue().b(jCurrentTimeMillis));
        }
    }
}
