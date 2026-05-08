package traviaut.b;

import java.util.concurrent.atomic.AtomicStampedReference;

/* JADX INFO: loaded from: traviaut.jar:traviaut/b/f.class */
public final class f<T> {
    private final AtomicStampedReference<T> a = new AtomicStampedReference<>(null, 0);

    public final T a(int[] iArr) {
        int i = iArr[0];
        T t = this.a.get(iArr);
        if (i == iArr[0]) {
            return null;
        }
        return t;
    }

    public final T a() {
        return this.a.getReference();
    }

    public final void a(T t) {
        this.a.set(t, this.a.getStamp() + 1);
    }
}
