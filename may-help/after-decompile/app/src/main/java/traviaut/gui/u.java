package traviaut.gui;

import java.awt.AWTException;
import java.awt.Container;
import java.awt.Dimension;
import java.awt.Font;
import java.awt.Frame;
import java.awt.GridBagLayout;
import java.awt.Image;
import java.awt.MenuItem;
import java.awt.PopupMenu;
import java.awt.SystemTray;
import java.awt.Toolkit;
import java.awt.TrayIcon;
import java.awt.event.ComponentAdapter;
import java.awt.event.ComponentEvent;
import java.awt.event.WindowAdapter;
import java.awt.event.WindowEvent;
import java.util.HashMap;
import java.util.Iterator;
import java.util.Map;
import javafx.application.Platform;
import javafx.embed.swing.JFXPanel;
import javax.swing.BoxLayout;
import javax.swing.JFrame;
import javax.swing.JPanel;
import javax.swing.JScrollPane;
import javax.swing.JSplitPane;
import javax.swing.JTextArea;
import javax.swing.UIManager;
import javax.swing.UnsupportedLookAndFeelException;
import javax.swing.plaf.metal.DefaultMetalTheme;
import javax.swing.plaf.metal.MetalLookAndFeel;
import traviaut.Main;
import traviaut.xml.TAGui;

/* JADX INFO: loaded from: traviaut.jar:traviaut/gui/u.class */
public final class u extends JFrame {
    private final Map<traviaut.e, n> b = new HashMap();
    private final JPanel c = new JPanel();
    private final JScrollPane d = new JScrollPane(this.c);
    public final s a = new s();
    private final Image e = Toolkit.getDefaultToolkit().getImage(getClass().getResource("/traviaut/gui/res/talogo.gif"));
    private TrayIcon f;
    private final t g;
    private int h;

    public final void a(traviaut.e eVar) {
        int iC = Main.c();
        if (iC != this.h) {
            this.h = iC;
            c();
        }
        this.g.a();
        n nVar = this.b.get(eVar);
        if (nVar != null) {
            nVar.a();
        }
    }

    public final boolean a(boolean z) {
        if (!Main.a.a(z)) {
            return false;
        }
        c();
        return true;
    }

    public final void a() {
        new a(this).setVisible(true);
    }

    public final void b() {
        if (this.f == null) {
            return;
        }
        try {
            if (SystemTray.isSupported()) {
                SystemTray.getSystemTray().add(this.f);
                setVisible(false);
            }
        } catch (IllegalArgumentException e) {
            traviaut.g.a("init tray failed: handled to override absence of global parameters: " + e.getMessage());
            setVisible(!isVisible());
        } catch (NoClassDefFoundError unused) {
            traviaut.g.a("minimize is not supported");
        } catch (AWTException e2) {
            traviaut.g.a("minimize failed: " + e2.getMessage());
        }
    }

    private void d() {
        if (this.f == null) {
            return;
        }
        SystemTray.getSystemTray().remove(this.f);
        setVisible(true);
        this.b.values().forEach((v0) -> {
            v0.a();
        });
    }

    public u() {
        Toolkit.getDefaultToolkit().setDynamicLayout(true);
        setDefaultCloseOperation(2);
        setTitle("Travian Automator - 20161021.0-tap");
        setIconImage(this.e);
        setAlwaysOnTop(traviaut.f.b().ontop);
        addComponentListener(new ComponentAdapter(this) { // from class: traviaut.gui.u.1
            public final void componentShown(ComponentEvent componentEvent) {
                if (traviaut.f.c().userID.isEmpty()) {
                    b bVar = new b();
                    bVar.a(new traviaut.gui.properties.o(traviaut.f.c()));
                    bVar.a((Frame) this, "");
                }
            }
        });
        addWindowListener(new WindowAdapter() { // from class: traviaut.gui.u.2
            public final void windowClosing(WindowEvent windowEvent) {
                u.a(u.this, windowEvent);
            }
        });
        try {
            UIManager.getLookAndFeel();
            MetalLookAndFeel.setCurrentTheme(new DefaultMetalTheme());
            UIManager.setLookAndFeel(new MetalLookAndFeel());
        } catch (UnsupportedLookAndFeelException unused) {
        }
        getContentPane().setLayout(new GridBagLayout());
        this.g = new t(this);
        getContentPane().add(this.g, new c(0, 0).a(1).a);
        this.c.setLayout(new BoxLayout(this.c, 1));
        JTextArea jTextArea = new JTextArea(6, 20);
        jTextArea.setEditable(false);
        jTextArea.setFont(new Font("Monospaced", 0, 12));
        traviaut.g.a(new d(jTextArea));
        this.d.setPreferredSize(new Dimension(traviaut.f.b().viewXSize, traviaut.f.b().viewYSize));
        this.d.getVerticalScrollBar().setUnitIncrement(14);
        JSplitPane jSplitPane = new JSplitPane(0, this.d, new JScrollPane(jTextArea));
        jSplitPane.setResizeWeight(1.0d);
        Container contentPane = getContentPane();
        c cVarA = new c(0, 1).a(1);
        cVarA.a.weightx = 1.0d;
        cVarA.a.weighty = 1.0d;
        contentPane.add(jSplitPane, cVarA.a);
        try {
            if (SystemTray.isSupported()) {
                PopupMenu popupMenu = new PopupMenu();
                this.f = new TrayIcon(this.e, "Travian Automator", popupMenu);
                this.f.addActionListener(actionEvent -> {
                    d();
                });
                MenuItem menuItem = new MenuItem("About");
                menuItem.addActionListener(actionEvent2 -> {
                    a();
                });
                popupMenu.add(menuItem);
                MenuItem menuItem2 = new MenuItem("Restore");
                menuItem2.addActionListener(actionEvent3 -> {
                    d();
                });
                popupMenu.add(menuItem2);
                popupMenu.addSeparator();
                MenuItem menuItem3 = new MenuItem("Exit");
                menuItem3.addActionListener(actionEvent4 -> {
                    d();
                    dispose();
                });
                popupMenu.add(menuItem3);
            }
        } catch (NoClassDefFoundError unused2) {
            traviaut.g.a("tray icon is not supported");
        }
        pack();
        if (traviaut.f.b().maximized) {
            setExtendedState(getExtendedState() | 6);
        }
        setLocationByPlatform(true);
        new JFXPanel();
        Platform.runLater(() -> {
            Platform.setImplicitExit(false);
        });
        Platform.runLater(() -> {
            org.a.a.a();
        });
        traviaut.k.a().a(new e(this));
    }

    private void c() {
        Iterator<n> it = this.b.values().iterator();
        while (it.hasNext()) {
            it.next().a(this.a);
        }
        this.b.clear();
        this.c.removeAll();
        for (traviaut.e eVar : Main.a.a) {
            n nVar = new n(this, eVar);
            this.b.put(eVar, nVar);
            this.c.add(nVar);
            nVar.a();
        }
        traviaut.c cVar = Main.a;
        if (cVar.a.isEmpty()) {
            return;
        }
        traviaut.k.a(cVar.a.get(0));
    }

    static /* synthetic */ void a(u uVar, WindowEvent windowEvent) {
        traviaut.k.a().a((e) null);
        TAGui tAGuiB = traviaut.f.b();
        Dimension size = uVar.d.getSize();
        tAGuiB.viewXSize = size.width;
        tAGuiB.viewYSize = size.height;
        tAGuiB.maximized = (uVar.getExtendedState() & 6) != 0;
        traviaut.f.a();
        Iterator<n> it = uVar.b.values().iterator();
        while (it.hasNext()) {
            it.next().a(uVar.a);
        }
        Platform.runLater(() -> {
            Platform.exit();
        });
    }
}
