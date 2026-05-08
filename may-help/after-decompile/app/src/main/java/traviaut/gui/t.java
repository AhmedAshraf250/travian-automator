package traviaut.gui;

import java.awt.Frame;
import javax.swing.JButton;
import javax.swing.JToggleButton;
import javax.swing.JToolBar;
import traviaut.Main;
import traviaut.gui.properties.B;
import traviaut.gui.properties.C0024c;
import traviaut.gui.properties.y;

/* JADX INFO: loaded from: traviaut.jar:traviaut/gui/t.class */
public final class t extends JToolBar {
    private final g a = new g();

    public final void a() {
        this.a.a();
    }

    public t(u uVar) {
        JButton jButton = new JButton("update");
        jButton.addActionListener(actionEvent -> {
            if (uVar.a(true)) {
                return;
            }
            Main.a.a.stream().forEach((v0) -> {
                v0.e();
            });
        });
        add(jButton);
        JButton jButton2 = new JButton("login");
        jButton2.addActionListener(actionEvent2 -> {
            p pVar = new p(uVar, true, Main.c);
            pVar.setVisible(true);
            if (pVar.a() == 1) {
                uVar.a(false);
            }
        });
        add(jButton2);
        JButton jButton3 = new JButton(f.CONF.b);
        jButton3.addActionListener(actionEvent3 -> {
            y yVar = new y("General");
            yVar.a(new traviaut.gui.properties.j(traviaut.f.c()));
            if (Main.a()) {
                yVar.a(new traviaut.gui.properties.p(traviaut.f.c()));
            }
            yVar.a(new B(traviaut.f.c().layout.resPriorities));
            b bVar = new b();
            bVar.a(yVar);
            if (Main.a()) {
                y yVar2 = new y("Hero");
                yVar2.a(new traviaut.gui.properties.l(traviaut.f.c().hero, false));
                new traviaut.gui.properties.k(traviaut.f.c().hero).a = Main.b();
                bVar.a(yVar2);
                bVar.a(new traviaut.gui.properties.n(traviaut.f.c().layout));
                if (Main.b()) {
                    traviaut.gui.properties.d.a(() -> {
                        traviaut.gui.properties.h hVar = new traviaut.gui.properties.h(traviaut.f.c().troopstraining);
                        bVar.a(new traviaut.gui.properties.d("Troops training", hVar, hVar));
                    });
                    bVar.a(new C0024c(traviaut.f.c().celeb));
                    bVar.a(new traviaut.gui.properties.q(traviaut.f.c().merchant));
                }
            }
            bVar.a((Frame) uVar, "Settings");
        });
        add(jButton3);
        JToggleButton jToggleButton = new JToggleButton("pause", traviaut.f.c().paused);
        jToggleButton.addActionListener(actionEvent4 -> {
            boolean zIsSelected = jToggleButton.isSelected();
            if (zIsSelected) {
                traviaut.b.b();
            } else {
                traviaut.b.c();
            }
            traviaut.f.c().paused = zIsSelected;
        });
        add(jToggleButton);
        JToggleButton jToggleButton2 = new JToggleButton("build", traviaut.f.c().builden);
        jToggleButton2.addActionListener(actionEvent5 -> {
            traviaut.f.c().builden = jToggleButton2.isSelected();
            traviaut.f.a();
        });
        add(jToggleButton2);
        JToggleButton jToggleButton3 = new JToggleButton("OnTop", traviaut.f.b().ontop);
        jToggleButton3.addActionListener(actionEvent6 -> {
            boolean zIsSelected = jToggleButton3.isSelected();
            uVar.setAlwaysOnTop(zIsSelected);
            traviaut.f.b().ontop = zIsSelected;
            traviaut.f.a();
        });
        add(jToggleButton3);
        JButton jButton4 = new JButton("about");
        jButton4.addActionListener(actionEvent7 -> {
            uVar.a();
        });
        add(jButton4);
        JButton jButton5 = new JButton("minim");
        jButton5.addActionListener(actionEvent8 -> {
            uVar.b();
        });
        add(jButton5);
        addSeparator();
        add(this.a);
        this.a.a();
    }
}
