package traviaut.xml;

import java.util.concurrent.TimeUnit;
import traviaut.Main;

/* JADX INFO: loaded from: traviaut.jar:traviaut/xml/TATroopsQueue.class */
public class TATroopsQueue {
    public int amount;
    public boolean allowfewer;
    public int queue;

    public TATroopsQueue() {
        this.amount = 90;
        this.queue = 20;
    }

    public TATroopsQueue(int i) {
        this.amount = 90;
        this.queue = 20;
        this.amount = i;
    }

    public int getMin() {
        if (Main.b() && !this.allowfewer) {
            return this.amount;
        }
        return 1;
    }

    public long getQueueMillis() {
        return TimeUnit.MINUTES.toMillis(Main.b() ? this.queue : 5L);
    }
}
