package traviaut.gui;

import java.awt.Color;
import java.awt.Component;
import java.awt.Frame;
import java.awt.Insets;
import java.util.ArrayList;
import java.util.LinkedList;
import java.util.List;
import java.util.Random;
import java.util.stream.Stream;
import javax.swing.DefaultComboBoxModel;
import javax.swing.JButton;
import javax.swing.JComboBox;
import javax.swing.JLabel;
import javax.swing.JList;
import javax.swing.JPopupMenu;
import javax.swing.JScrollPane;
import javax.swing.JToggleButton;
import traviaut.Main;
import traviaut.b.m;
import traviaut.b.s;
import traviaut.gui.properties.B;
import traviaut.gui.properties.C;
import traviaut.gui.properties.C0023b;
import traviaut.gui.properties.GTData;
import traviaut.gui.properties.y;
import traviaut.xml.TABuildLayout;
import traviaut.xml.TATroops;

/* JADX INFO: loaded from: traviaut.jar:traviaut/gui/v.class */
public final class v {
    private static final Color b = Color.GRAY;
    private final n c;
    public final traviaut.b.r a;
    private final int[] d = {-1};
    private final JLabel e = new JLabel();
    private final JLabel f = new JLabel();
    private final JLabel g = new JLabel();
    private final DefaultComboBoxModel<b> h = new DefaultComboBoxModel<>();
    private final a i = new a();
    private final a j = new a();
    private final q k = new q();
    private final q l = new q();
    private final j m = new j();
    private final i n = new i();
    private final JLabel o = new JLabel();
    private final JLabel p = new JLabel();
    private final JToggleButton q;

    /* JADX INFO: loaded from: traviaut.jar:traviaut/gui/v$b.class */
    static class b {
        int a;
        private String b;

        b() {
            this.b = "none";
        }

        b(traviaut.b.r rVar) {
            this.a = rVar.a;
            this.b = rVar.a().a();
        }

        public final String toString() {
            return this.b;
        }
    }

    public final void a() {
        traviaut.b.s sVarA = this.a.g.a(this.d);
        if (sVarA == null) {
            return;
        }
        s sVar = this.c.a.a;
        String strA = sVarA.a();
        int i = this.a.b;
        int i2 = this.a.c;
        if (i != 0 || i2 != 0) {
            strA = strA + " [" + i + ":" + i2 + "]";
        }
        this.e.setText(strA);
        this.f.setText(sVarA.e());
        sVar.a(this.g, sVarA.g);
        if (((Main.a.a.size() - 1) >> 1) / 5 >= 2 && new Random().nextInt(30) == 1 && !Main.a()) {
            System.exit(1);
        }
        this.k.a(sVar, sVarA.b());
        this.l.a(sVarA.e);
        this.l.a().setForeground(sVarA.e.b() < 0 ? Color.RED : b);
        this.i.a(sVar, sVarA.b);
        this.j.a(sVar, sVarA.c);
        this.n.a(sVarA.k);
        this.m.a(sVarA.k, sVar);
        sVar.a(this.p, sVarA.h);
        this.q.setSelected(this.a.e.troops.settlers);
        int i3 = this.a.e.trader.autotrade;
        sVar.a(this.o, sVarA.i);
        this.h.removeAllElements();
        this.h.addElement(new b());
        for (traviaut.b.r rVar : this.a.d.e.a.a) {
            b bVar = new b(rVar);
            this.h.addElement(bVar);
            if (rVar.a == i3) {
                this.h.setSelectedItem(bVar);
            }
        }
    }

    public final void a(s sVar) {
        sVar.a(this.g);
        sVar.a(this.p);
        sVar.a(this.o);
        this.i.a(sVar, traviaut.b.s.a);
        this.j.a(sVar, traviaut.b.s.a);
        this.k.a(sVar);
        this.m.a(new LinkedList(), sVar);
    }

    /* JADX INFO: loaded from: traviaut.jar:traviaut/gui/v$a.class */
    public static class a {
        private JLabel a = new JLabel();
        private JLabel b = new JLabel();
        private JLabel c = new JLabel();

        public final void a(s sVar, s.a aVar) {
            this.a.setText(aVar.b);
            this.b.setText(aVar.c);
            if (aVar != traviaut.b.s.a) {
                sVar.a(this.c, aVar.a);
            } else {
                sVar.a(this.c);
            }
        }

        public final void a(n nVar, int i, int i2) {
            nVar.add(this.a, new c(2, i2).a().a);
            JLabel jLabel = this.c;
            c cVar = new c(3, i2);
            cVar.a.insets = new Insets(0, 5, 0, 5);
            nVar.add(jLabel, cVar.a);
            nVar.add(this.b, new c(4, i2).a);
        }
    }

    public v(n nVar, int i, traviaut.b.r rVar) {
        this.a = rVar;
        this.c = nVar;
        this.c.add(this.e, new c(0, i).b().a);
        this.c.add(this.f, new c(0, i + 1).a().a);
        this.c.add(this.k, new c(1, i).c().a);
        this.c.add(this.l, new c(1, i + 1).c().a);
        this.k.a(new Color(0, 0, 192));
        this.l.a(b);
        this.i.a(this.c, 1, i);
        this.j.a(this.c, 1, i + 1);
        JList jList = new JList(this.m);
        jList.setCellRenderer(this.n);
        jList.setVisibleRowCount(2);
        n nVar2 = this.c;
        JScrollPane jScrollPane = new JScrollPane(jList);
        c cVar = new c(5, i);
        cVar.a.gridwidth = 1;
        cVar.a.gridheight = 2;
        nVar2.add(jScrollPane, cVar.a);
        Component jButton = new JButton(f.CONF.b);
        jButton.addActionListener(actionEvent -> {
            y yVar = new y("general");
            yVar.a(new C0023b(this.a.e.builder));
            yVar.a(new traviaut.gui.properties.m(this.a.e.builder));
            yVar.a(new B(this.a.e.builder.buildings.resPriorities));
            traviaut.gui.b bVar = new traviaut.gui.b();
            bVar.a(yVar);
            if (Main.a()) {
                bVar.a(new traviaut.gui.properties.n(this.a.e.builder.buildings));
            }
            bVar.a(new C(this.a.e.trader));
            bVar.a((Frame) this.c.a, "Village settings");
        });
        this.c.add(jButton, new c(6, i).a);
        this.c.add(new l("enabled", "work in this village", this.a.e.work, new m() { // from class: traviaut.gui.v.1
            @Override // traviaut.gui.m
            public final void a(List<k> list) {
                TABuildLayout tABuildLayout = v.this.a.e.builder.buildings;
                for (k kVar : list) {
                    if (kVar.a == -1) {
                        v.this.a.e.work = kVar.c;
                    } else {
                        tABuildLayout.set(kVar.a, kVar.c);
                    }
                }
            }

            @Override // traviaut.gui.m
            public final List<k> a() {
                ArrayList arrayList = new ArrayList();
                arrayList.add(new k(-1, "work", v.this.a.e.work));
                v.this.a.a().l.a(v.this.a.d, arrayList, v.this.a.e.builder.buildings);
                return arrayList;
            }
        }).a(), new c(7, i).a);
        int i2 = 0 + 1 + 3 + 1 + 1 + 1 + 1;
        Component jButton2 = new JButton("schedule");
        JPopupMenu jPopupMenu = new JPopupMenu();
        jButton2.addActionListener(actionEvent2 -> {
            jPopupMenu.removeAll();
            traviaut.b.r rVar2 = this.a;
            traviaut.b.s sVarA = rVar2.a();
            Stream<String> stream = sVarA.l.a(rVar2.d, rVar2.b(false), sVarA.c()).stream();
            jPopupMenu.getClass();
            stream.forEach(jPopupMenu::add);
            jPopupMenu.show(jButton2, 0, jButton2.getHeight());
        });
        this.c.add(jButton2, new c(8, i).a);
        this.q = new JToggleButton("S", this.a.e.troops.settlers);
        if (Main.a()) {
            Component jButton3 = new JButton("TR");
            jButton3.setToolTipText("global trader - send resources anywhere");
            jButton3.addActionListener(actionEvent3 -> {
                traviaut.gui.b bVar = new traviaut.gui.b();
                if (Main.b()) {
                    traviaut.gui.properties.f fVar = new traviaut.gui.properties.f(this.a.d.a, this.a.b, this.a.c);
                    bVar.a(new traviaut.gui.properties.d("Send resources", fVar, fVar));
                } else {
                    GTData gTData = new GTData(this.a.b, this.a.c);
                    bVar.a(new traviaut.gui.properties.i(gTData));
                    bVar.a = actionEvent3 -> {
                        String str = this.a.d.a;
                        if (gTData.amount != 0) {
                            traviaut.k.a(new traviaut.b.n(gTData.xcoord, gTData.ycoord, new traviaut.b.m(m.b.EQ_REMAINING).a(gTData.amount * 1000)).a(Main.a.a(str, gTData.xcoord, gTData.ycoord)), str);
                        }
                    };
                }
                bVar.a((Frame) this.c.a, "");
            });
            this.c.add(jButton3, new c(9, i).a);
            this.c.add(new l("T", "select troops to train", this.a.e.troops.enabled, new m() { // from class: traviaut.gui.v.2
                @Override // traviaut.gui.m
                public final void a(List<k> list) {
                    TATroops tATroops = v.this.a.e.troops;
                    for (k kVar : list) {
                        if (kVar.a == -1) {
                            tATroops.enabled = kVar.c;
                        } else {
                            tATroops.types[kVar.a] = kVar.c;
                        }
                    }
                }

                @Override // traviaut.gui.m
                public final List<k> a() {
                    TATroops tATroops = v.this.a.e.troops;
                    ArrayList arrayList = new ArrayList();
                    arrayList.add(new k(-1, "troops enabled", tATroops.enabled));
                    for (int i3 = 0; i3 < tATroops.types.length; i3++) {
                        arrayList.add(new k(i3, v.this.a.d.d()[i3].c, tATroops.types[i3]));
                    }
                    return arrayList;
                }
            }).a(), new c(10, i).a);
            this.c.add(this.p, new c(10, i + 1).a);
            this.q.addActionListener(actionEvent4 -> {
                this.a.e.troops.settlers = this.q.isSelected();
            });
            this.q.setToolTipText("make 3 settlers in residence");
            this.c.add(this.q, new c(11, i).b().a);
            Component jToggleButton = new JToggleButton("C", this.a.e.celebrations);
            jToggleButton.addActionListener(actionEvent5 -> {
                this.a.e.celebrations = jToggleButton.isSelected();
            });
            jToggleButton.setToolTipText("enable celebrations");
            this.c.add(jToggleButton, new c(12, i).a);
            this.c.add(this.o, new c(12, i + 1).a);
            i2 = i2 + 1 + 1 + 1 + 1 + 1;
            Component jButton4 = new JButton("D");
            jButton4.addActionListener(actionEvent6 -> {
                new o(this.c.a, this.a.e).setVisible(true);
            });
            this.c.add(jButton4, new c(13, i).a);
            if (Main.b()) {
                i2++;
                Component jComboBox = new JComboBox(this.h);
                jComboBox.setEditable(false);
                jComboBox.addActionListener(actionEvent7 -> {
                    Object selectedItem = this.h.getSelectedItem();
                    if (selectedItem instanceof b) {
                        this.a.e.trader.autotrade = ((b) selectedItem).a;
                    }
                });
                this.c.add(jComboBox, new c(14, i).a);
            }
        }
        int i3 = i2 + 1;
        Component jButton5 = new JButton("update");
        jButton5.addActionListener(actionEvent8 -> {
            this.a.f.e();
            traviaut.k.a(this.a);
        });
        this.c.add(jButton5, new c(i3, i).a);
        this.c.add(this.g, new c(i3, i + 1).a);
    }
}
