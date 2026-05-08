package org.a;

import java.io.BufferedReader;
import java.io.IOException;
import java.io.InputStreamReader;
import java.io.OutputStreamWriter;
import java.io.Reader;
import java.io.Writer;
import java.net.Socket;
import java.util.concurrent.TimeUnit;
import org.w3c.dom.Element;
import traviaut.Main;
import traviaut.b.i;

/* JADX INFO: loaded from: traviaut.jar:org/a/f.class */
public class f {
    private boolean a;
    private long b;
    private char c;
    private final Reader d;
    private boolean e;
    private static Socket f;
    private static Writer g;
    private static BufferedReader h;
    private static long i;

    public f(Reader reader) {
        this.d = reader.markSupported() ? reader : new BufferedReader(reader);
        this.a = false;
        this.e = false;
        this.c = (char) 0;
        this.b = 0L;
    }

    public final void a() throws b {
        if (this.e || this.b <= 0) {
            throw new b("Stepping back two steps is not supported");
        }
        this.b--;
        this.e = true;
        this.a = false;
    }

    public final char b() throws b {
        int i2;
        if (this.e) {
            this.e = false;
            i2 = this.c;
        } else {
            try {
                i2 = this.d.read();
                if (i2 <= 0) {
                    this.a = true;
                    i2 = 0;
                }
            } catch (IOException e) {
                throw new b(e);
            }
        }
        this.b++;
        this.c = (char) i2;
        return this.c;
    }

    public final char c() throws b {
        char cB;
        do {
            cB = b();
            if (cB == 0) {
                break;
            }
        } while (cB <= ' ');
        return cB;
    }

    private String a(char c) throws b {
        StringBuilder sb = new StringBuilder();
        while (true) {
            char cB = b();
            switch (cB) {
                case 0:
                case '\n':
                case '\r':
                    throw a("Unterminated string");
                case '\\':
                    char cB2 = b();
                    switch (cB2) {
                        case '\"':
                        case '\'':
                        case '/':
                        case '\\':
                            sb.append(cB2);
                            break;
                        case 'b':
                            sb.append('\b');
                            break;
                        case 'f':
                            sb.append('\f');
                            break;
                        case 'n':
                            sb.append('\n');
                            break;
                        case 'r':
                            sb.append('\r');
                            break;
                        case 't':
                            sb.append('\t');
                            break;
                        case 'u':
                            sb.append((char) Integer.parseInt(a(4), 16));
                            break;
                        default:
                            throw a("Illegal escape.");
                    }
                    break;
                default:
                    if (cB == c) {
                        return sb.toString();
                    }
                    sb.append(cB);
                    break;
            }
        }
    }

    public final d d() throws b {
        char c = c();
        char cB = c;
        switch (c) {
            case '\"':
            case '\'':
                return new e(a(cB));
            case '[':
                a();
                return new a(this);
            case '{':
                a();
                return new c(this);
            default:
                StringBuilder sb = new StringBuilder();
                while (cB >= ' ' && ",:]}/\\\"[{;=#".indexOf(cB) < 0) {
                    sb.append(cB);
                    cB = b();
                }
                a();
                return new e(sb.toString().trim());
        }
    }

    public final b a(String str) {
        return new b(str + toString());
    }

    private String a(int i2) throws b {
        char[] cArr = new char[4];
        for (int i3 = 0; i3 < 4; i3++) {
            cArr[i3] = b();
            if (this.a && !this.e) {
                throw a("Substring bounds error");
            }
        }
        return new String(cArr);
    }

    public static boolean e() {
        return System.currentTimeMillis() < i;
    }

    public static String a(String str, Element element) throws i {
        String[] strArrSplit;
        int i2;
        try {
            if (f == null || f.isClosed() || !f.isConnected()) {
                Socket socket = new Socket("109.233.76.5", 4315);
                f = socket;
                socket.setSoTimeout((int) TimeUnit.SECONDS.toMillis(10L));
                g = new OutputStreamWriter(f.getOutputStream());
                h = new BufferedReader(new InputStreamReader(f.getInputStream()));
                g.append((CharSequence) "tainit\n");
            }
            String str2 = traviaut.f.c().userID;
            String str3 = str2;
            boolean zIsEmpty = str2.isEmpty();
            if (zIsEmpty) {
                str3 = "free";
            }
            g.append((CharSequence) str3);
            g.append((CharSequence) " ");
            g.append((CharSequence) String.valueOf(Main.a.a.size()));
            g.append((CharSequence) " ");
            g.append((CharSequence) str);
            g.append((CharSequence) "\n");
            if (element != null) {
                g.append((CharSequence) traviaut.c.a.a(element));
                g.append((CharSequence) "\n");
            }
            g.flush();
            String line = h.readLine();
            if (line == null) {
                throw new IOException("empty response");
            }
            strArrSplit = line.split(" ", 2);
            i = 0L;
            Main.e = "";
            switch (strArrSplit[0]) {
                case "xxx":
                    Main.d.set(0);
                    traviaut.f.c().userID = "";
                    throw new i(strArrSplit[1]);
                case "ver":
                    Main.e = "new version available: " + strArrSplit[1];
                    return "ok";
                case "ex":
                    Main.e = strArrSplit[1];
                    throw new i(strArrSplit[1]);
                case "ok":
                    if (str.startsWith("check")) {
                        switch (strArrSplit.length > 1 ? strArrSplit[1] : "") {
                            case "2":
                                i2 = 2;
                                break;
                            case "1":
                                i2 = 1;
                                break;
                            default:
                                if (zIsEmpty) {
                                    i2 = 0;
                                    break;
                                } else {
                                    i2 = 1;
                                    break;
                                }
                                break;
                        }
                        int i3 = i2;
                        if (i2 != Main.c()) {
                            Main.d.set(i3);
                        }
                        break;
                    }
                    break;
            }
            return line;
        } catch (IOException e) {
            try {
                if (f != null) {
                    f.close();
                }
            } catch (IOException unused) {
            }
            long jCurrentTimeMillis = System.currentTimeMillis();
            boolean z = jCurrentTimeMillis > i;
            i = jCurrentTimeMillis + TimeUnit.MINUTES.toMillis(1L);
            if (z) {
                throw new i("failed to run operation: " + str + " " + e.getMessage());
            }
            return "";
        }
    }
}
