package traviaut.d;

/* JADX INFO: loaded from: traviaut.jar:traviaut/d/a.class */
public abstract class a {
    private String a;

    public final void a(String str) {
        this.a = str;
    }

    protected abstract String a(long j);

    public final String b(long j) {
        StringBuilder sb = new StringBuilder();
        if (this.a != null) {
            sb.append(this.a).append(" ");
        }
        sb.append(a(j));
        return sb.toString();
    }

    public abstract int a();
}
