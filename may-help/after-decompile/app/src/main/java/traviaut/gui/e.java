package traviaut.gui;

import java.util.Queue;
import java.util.concurrent.ConcurrentLinkedQueue;
import javax.swing.SwingUtilities;

/* JADX INFO: loaded from: traviaut.jar:traviaut/gui/e.class */
public final class e implements Runnable {
    private final Queue<traviaut.e> a = new ConcurrentLinkedQueue();
    private final u b;

    public e(u uVar) {
        this.b = uVar;
    }

    public final void a(traviaut.e eVar) {
        this.a.offer(eVar);
        SwingUtilities.invokeLater(this);
    }

    @Override // java.lang.Runnable
    public final void run() {
        while (true) {
            traviaut.e eVarPoll = this.a.poll();
            if (eVarPoll == null) {
                return;
            } else {
                this.b.a(eVarPoll);
            }
        }
    }
}
