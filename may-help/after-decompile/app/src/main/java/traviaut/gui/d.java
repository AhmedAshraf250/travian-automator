package traviaut.gui;

import java.util.Queue;
import java.util.concurrent.ConcurrentLinkedQueue;
import javax.swing.JTextArea;
import javax.swing.SwingUtilities;

/* JADX INFO: loaded from: traviaut.jar:traviaut/gui/d.class */
public final class d implements Runnable, traviaut.h {
    private final Queue<String> a = new ConcurrentLinkedQueue();
    private final JTextArea b;

    public d(JTextArea jTextArea) {
        this.b = jTextArea;
    }

    @Override // traviaut.h
    public final void addLog(String str) {
        this.a.offer(str);
        SwingUtilities.invokeLater(this);
    }

    @Override // java.lang.Runnable
    public final void run() {
        while (true) {
            String strPoll = this.a.poll();
            if (strPoll == null) {
                return;
            } else {
                this.b.append(strPoll + "\n");
            }
        }
    }
}
