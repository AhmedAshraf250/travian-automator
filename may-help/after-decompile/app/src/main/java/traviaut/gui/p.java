package traviaut.gui;

import java.awt.Font;
import java.awt.Frame;
import java.awt.GridBagConstraints;
import java.awt.GridBagLayout;
import java.awt.Insets;
import java.awt.Toolkit;
import java.awt.event.ActionEvent;
import java.awt.event.ActionListener;
import java.awt.event.ComponentAdapter;
import java.awt.event.ComponentEvent;
import java.awt.event.WindowAdapter;
import java.awt.event.WindowEvent;
import java.io.File;
import java.io.FileInputStream;
import java.io.FileOutputStream;
import java.io.IOException;
import javax.swing.JButton;
import javax.swing.JDialog;
import javax.swing.JScrollPane;
import javax.swing.JTextArea;
import javax.swing.text.DefaultEditorKit;
import javax.swing.text.Document;

/* JADX INFO: loaded from: traviaut.jar:traviaut/gui/p.class */
public final class p extends JDialog {
    private final String a;
    private JButton b;
    private JTextArea c;
    private JScrollPane d;
    private JButton e;
    private int f;

    public final int a() {
        return this.f;
    }

    /* JADX INFO: Access modifiers changed from: private */
    public void a(int i) {
        this.f = i;
        setVisible(false);
        dispose();
    }

    public p(Frame frame, boolean z, String str) {
        super(frame, true);
        this.f = 0;
        Toolkit.getDefaultToolkit().setDynamicLayout(true);
        this.a = str;
        this.d = new JScrollPane();
        this.c = new JTextArea();
        this.b = new JButton();
        this.e = new JButton();
        getContentPane().setLayout(new GridBagLayout());
        addComponentListener(new ComponentAdapter() { // from class: traviaut.gui.p.1
            public final void componentShown(ComponentEvent componentEvent) {
                p.a(p.this, componentEvent);
            }
        });
        addWindowListener(new WindowAdapter() { // from class: traviaut.gui.p.2
            public final void windowClosing(WindowEvent windowEvent) {
                p.this.a(0);
            }
        });
        this.c.setColumns(60);
        this.c.setFont(new Font("Courier New", 0, 12));
        this.c.setRows(20);
        this.d.setViewportView(this.c);
        GridBagConstraints gridBagConstraints = new GridBagConstraints();
        gridBagConstraints.gridwidth = 0;
        gridBagConstraints.fill = 1;
        gridBagConstraints.weightx = 1.0d;
        gridBagConstraints.weighty = 1.0d;
        gridBagConstraints.insets = new Insets(10, 10, 10, 10);
        getContentPane().add(this.d, gridBagConstraints);
        this.b.setText("Cancel");
        this.b.addActionListener(new ActionListener() { // from class: traviaut.gui.p.3
            public final void actionPerformed(ActionEvent actionEvent) {
                p.this.a(0);
            }
        });
        GridBagConstraints gridBagConstraints2 = new GridBagConstraints();
        gridBagConstraints2.gridx = 2;
        gridBagConstraints2.gridy = 1;
        gridBagConstraints2.anchor = 17;
        gridBagConstraints2.weightx = 0.1d;
        getContentPane().add(this.b, gridBagConstraints2);
        this.e.setText("OK");
        this.e.addActionListener(new ActionListener() { // from class: traviaut.gui.p.4
            public final void actionPerformed(ActionEvent actionEvent) {
                p.b(p.this, actionEvent);
            }
        });
        GridBagConstraints gridBagConstraints3 = new GridBagConstraints();
        gridBagConstraints3.gridx = 1;
        gridBagConstraints3.gridy = 1;
        gridBagConstraints3.anchor = 13;
        gridBagConstraints3.weightx = 0.1d;
        getContentPane().add(this.e, gridBagConstraints3);
        pack();
        setLocationRelativeTo(frame);
    }

    static /* synthetic */ void a(p pVar, ComponentEvent componentEvent) {
        FileInputStream fileInputStream = null;
        try {
            try {
                fileInputStream = new FileInputStream(new File(pVar.a));
                new DefaultEditorKit().read(fileInputStream, pVar.c.getDocument(), 0);
                try {
                    fileInputStream.close();
                } catch (IOException e) {
                    traviaut.g.a("failed to close settings stream", e);
                }
            } catch (Exception e2) {
                traviaut.g.a("failed to load settings", e2);
                if (fileInputStream != null) {
                    try {
                        fileInputStream.close();
                    } catch (IOException e3) {
                        traviaut.g.a("failed to close settings stream", e3);
                    }
                }
            }
        } catch (Throwable th) {
            if (fileInputStream != null) {
                try {
                    fileInputStream.close();
                } catch (IOException e4) {
                    traviaut.g.a("failed to close settings stream", e4);
                    throw th;
                }
            }
            throw th;
        }
    }

    static /* synthetic */ void b(p pVar, ActionEvent actionEvent) {
        FileOutputStream fileOutputStream = null;
        try {
            try {
                fileOutputStream = new FileOutputStream(new File(pVar.a));
                Document document = pVar.c.getDocument();
                new DefaultEditorKit().write(fileOutputStream, document, 0, document.getLength());
                try {
                    fileOutputStream.close();
                } catch (IOException e) {
                    traviaut.g.a("failed to close settings stream", e);
                }
            } catch (Throwable th) {
                if (fileOutputStream != null) {
                    try {
                        fileOutputStream.close();
                    } catch (IOException e2) {
                        traviaut.g.a("failed to close settings stream", e2);
                        throw th;
                    }
                }
                throw th;
            }
        } catch (Exception e3) {
            traviaut.g.a("failed to save settings", e3);
            if (fileOutputStream != null) {
                try {
                    fileOutputStream.close();
                } catch (IOException e4) {
                    traviaut.g.a("failed to close settings stream", e4);
                }
            }
        }
        pVar.a(1);
    }
}
