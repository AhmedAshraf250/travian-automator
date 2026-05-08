package traviaut.gui.properties;

import java.awt.Dimension;
import java.util.concurrent.CountDownLatch;
import javafx.application.Platform;
import javafx.embed.swing.JFXPanel;
import javafx.scene.Scene;
import javafx.scene.layout.Pane;
import javax.swing.JComponent;

/* JADX INFO: loaded from: traviaut.jar:traviaut/gui/properties/d.class */
public final class d extends u {
    private final String a;
    private final x b;
    private final Pane c;

    public d(String str, x xVar, Pane pane) {
        this.a = str;
        this.b = xVar;
        this.c = pane;
    }

    @Override // traviaut.gui.properties.u
    public final String a() {
        return this.a;
    }

    public static void a(Runnable runnable) {
        if (Platform.isFxApplicationThread()) {
            runnable.run();
            return;
        }
        CountDownLatch countDownLatch = new CountDownLatch(1);
        Platform.runLater(() -> {
            runnable.run();
            countDownLatch.countDown();
        });
        try {
            countDownLatch.await();
        } catch (InterruptedException e) {
            System.out.println("wait for jfx interrupted: " + e);
        }
    }

    @Override // traviaut.gui.properties.u
    public final JComponent b() {
        JFXPanel jFXPanel = new JFXPanel();
        a(() -> {
            jFXPanel.setScene(new Scene(this.c));
            jFXPanel.setPreferredSize(new Dimension(((int) this.c.getWidth()) + 10, ((int) this.c.getHeight()) + 10));
        });
        return jFXPanel;
    }

    @Override // traviaut.gui.properties.u, traviaut.gui.properties.x
    public final void c() {
        a(() -> {
            this.b.c();
        });
    }

    @Override // traviaut.gui.properties.x
    public final void d() {
        a(() -> {
            this.b.d();
        });
    }
}
