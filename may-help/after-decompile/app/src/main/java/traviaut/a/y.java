package traviaut.a;

import org.w3c.dom.Element;
import traviaut.a.x;
import traviaut.b.t;

/* JADX INFO: loaded from: traviaut.jar:traviaut/a/y.class */
public final class y extends b {
    @Override // traviaut.a.b
    public final boolean a(traviaut.b.r rVar) {
        return rVar.e.troops.enabled && rVar.d.e.c() != traviaut.l.UNKNOWN;
    }

    @Override // traviaut.a.b
    public final void a(long j, traviaut.b.r rVar, traviaut.b.l lVar) {
        w wVar = new w(rVar);
        for (int i = 0; i < 3; i++) {
            int iA = a(rVar, i);
            if (iA != -1 && wVar.a(iA, j, traviaut.b.r.g())) {
                lVar.a(wVar.e(iA));
            }
        }
    }

    @Override // traviaut.a.b
    public final boolean b(traviaut.b.r rVar) {
        traviaut.b.l lVarC = rVar.a().c();
        w wVar = new w(rVar);
        for (int i = 0; i < 3; i++) {
            int iA = a(rVar, i);
            if (iA != -1 && wVar.c(iA).e(lVarC) && wVar.b(iA)) {
                return true;
            }
        }
        return false;
    }

    public static int a(Element element, int i) throws traviaut.b.i {
        if (element == null) {
            return -1;
        }
        return traviaut.m.c(org.a.f.a("unit " + i, element));
    }

    private static int a(traviaut.b.r rVar, int i) {
        if (rVar.a().l.e(i + 19) == 0) {
            return -1;
        }
        x.a[] aVarArrD = rVar.d.d();
        boolean[] zArr = rVar.e.troops.types;
        int iMin = Math.min(aVarArrD.length, zArr.length);
        for (int i2 = 0; i2 < iMin; i2++) {
            if (aVarArrD[i2].b == i && zArr[i2]) {
                return i2;
            }
        }
        return -1;
    }

    /*  JADX ERROR: JadxRuntimeException in pass: ConstructorVisitor
        jadx.core.utils.exceptions.JadxRuntimeException: Can't remove SSA var: r1v39 ??, still in use, count: 1, list:
          (r1v39 ?? I:traviaut.b.j) from 0x015e: INVOKE (r1v40 ?? I:traviaut.b.j) = (r1v39 ?? I:traviaut.b.j) VIRTUAL call: traviaut.b.j.a():traviaut.b.j A[MD:():traviaut.b.j (m)]
        	at jadx.core.utils.InsnRemover.removeSsaVar(InsnRemover.java:162)
        	at jadx.core.utils.InsnRemover.unbindResult(InsnRemover.java:127)
        	at jadx.core.utils.InsnRemover.lambda$unbindInsns$1(InsnRemover.java:99)
        	at java.base/java.util.ArrayList.forEach(Unknown Source)
        	at jadx.core.utils.InsnRemover.unbindInsns(InsnRemover.java:98)
        	at jadx.core.utils.InsnRemover.perform(InsnRemover.java:73)
        	at jadx.core.dex.visitors.ConstructorVisitor.replaceInvoke(ConstructorVisitor.java:59)
        	at jadx.core.dex.visitors.ConstructorVisitor.visit(ConstructorVisitor.java:42)
        */
    @Override // traviaut.a.k
    public final void a(
    /*  JADX ERROR: JadxRuntimeException in pass: ConstructorVisitor
        jadx.core.utils.exceptions.JadxRuntimeException: Can't remove SSA var: r1v39 ??, still in use, count: 1, list:
          (r1v39 ?? I:traviaut.b.j) from 0x015e: INVOKE (r1v40 ?? I:traviaut.b.j) = (r1v39 ?? I:traviaut.b.j) VIRTUAL call: traviaut.b.j.a():traviaut.b.j A[MD:():traviaut.b.j (m)]
        	at jadx.core.utils.InsnRemover.removeSsaVar(InsnRemover.java:162)
        	at jadx.core.utils.InsnRemover.unbindResult(InsnRemover.java:127)
        	at jadx.core.utils.InsnRemover.lambda$unbindInsns$1(InsnRemover.java:99)
        	at java.base/java.util.ArrayList.forEach(Unknown Source)
        	at jadx.core.utils.InsnRemover.unbindInsns(InsnRemover.java:98)
        	at jadx.core.utils.InsnRemover.perform(InsnRemover.java:73)
        	at jadx.core.dex.visitors.ConstructorVisitor.replaceInvoke(ConstructorVisitor.java:59)
        */
    /*  JADX ERROR: Method generation error
        jadx.core.utils.exceptions.JadxRuntimeException: Code variable not set in r6v0 ??
        	at jadx.core.dex.instructions.args.SSAVar.getCodeVar(SSAVar.java:236)
        	at jadx.core.codegen.MethodGen.addMethodArguments(MethodGen.java:224)
        	at jadx.core.codegen.MethodGen.addDefinition(MethodGen.java:169)
        	at jadx.core.codegen.ClassGen.addMethodCode(ClassGen.java:407)
        	at jadx.core.codegen.ClassGen.addMethod(ClassGen.java:337)
        	at jadx.core.codegen.ClassGen.lambda$addInnerClsAndMethods$2(ClassGen.java:303)
        	at java.base/java.util.stream.ForEachOps$ForEachOp$OfRef.accept(Unknown Source)
        	at java.base/java.util.ArrayList.forEach(Unknown Source)
        	at java.base/java.util.stream.SortedOps$RefSortingSink.end(Unknown Source)
        	at java.base/java.util.stream.Sink$ChainedReference.end(Unknown Source)
        	at java.base/java.util.stream.ReferencePipeline$7$1FlatMap.end(Unknown Source)
        	at java.base/java.util.stream.AbstractPipeline.copyInto(Unknown Source)
        	at java.base/java.util.stream.AbstractPipeline.wrapAndCopyInto(Unknown Source)
        	at java.base/java.util.stream.ForEachOps$ForEachOp.evaluateSequential(Unknown Source)
        	at java.base/java.util.stream.ForEachOps$ForEachOp$OfRef.evaluateSequential(Unknown Source)
        	at java.base/java.util.stream.AbstractPipeline.evaluate(Unknown Source)
        	at java.base/java.util.stream.ReferencePipeline.forEach(Unknown Source)
        	at jadx.core.codegen.ClassGen.addInnerClsAndMethods(ClassGen.java:299)
        	at jadx.core.codegen.ClassGen.addClassBody(ClassGen.java:288)
        	at jadx.core.codegen.ClassGen.addClassBody(ClassGen.java:272)
        	at jadx.core.codegen.ClassGen.addClassCode(ClassGen.java:159)
        	at jadx.core.codegen.ClassGen.makeClass(ClassGen.java:103)
        	at jadx.core.codegen.CodeGen.wrapCodeGen(CodeGen.java:45)
        	at jadx.core.codegen.CodeGen.generateJavaCode(CodeGen.java:34)
        	at jadx.core.codegen.CodeGen.generate(CodeGen.java:22)
        	at jadx.core.ProcessClass.process(ProcessClass.java:88)
        	at jadx.core.ProcessClass.generateCode(ProcessClass.java:126)
        	at jadx.core.dex.nodes.ClassNode.generateClassCode(ClassNode.java:405)
        	at jadx.core.dex.nodes.ClassNode.decompile(ClassNode.java:393)
        	at jadx.core.dex.nodes.ClassNode.getCode(ClassNode.java:343)
        */

    private static void a(long j, traviaut.b.r rVar, int i) {
        if (j <= 0) {
            return;
        }
        long jCurrentTimeMillis = j + System.currentTimeMillis();
        rVar.f.a(t.a.values()[i + t.a.TROOPER1.ordinal()], jCurrentTimeMillis, true);
    }
}
