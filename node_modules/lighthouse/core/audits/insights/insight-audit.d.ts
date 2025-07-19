export type CreateDetailsExtras = {
    insights: import("@paulirish/trace_engine/models/trace/insights/types.js").InsightSet;
    parsedTrace: LH.Artifacts.TraceEngineResult["data"];
};
/**
 * @typedef CreateDetailsExtras
 * @property {import('@paulirish/trace_engine/models/trace/insights/types.js').InsightSet} insights
 * @property {LH.Artifacts.TraceEngineResult['data']} parsedTrace
 */
/**
 * @param {LH.Artifacts} artifacts
 * @param {LH.Audit.Context} context
 * @param {T} insightName
 * @param {(insight: import('@paulirish/trace_engine/models/trace/insights/types.js').InsightModels[T], extras: CreateDetailsExtras) => LH.Audit.Details|undefined} createDetails
 * @template {keyof import('@paulirish/trace_engine/models/trace/insights/types.js').InsightModelsType} T
 * @return {Promise<LH.Audit.Product>}
 */
export function adaptInsightToAuditProduct<T extends keyof import("@paulirish/trace_engine/models/trace/insights/types.js").InsightModelsType>(artifacts: LH.Artifacts, context: LH.Audit.Context, insightName: T, createDetails: (insight: import("@paulirish/trace_engine/models/trace/insights/types.js").InsightModels[T], extras: CreateDetailsExtras) => LH.Audit.Details | undefined): Promise<LH.Audit.Product>;
/**
 * @param {LH.Artifacts.TraceElement[]} traceElements
 * @param {number|null|undefined} nodeId
 * @return {LH.Audit.Details.NodeValue|undefined}
 */
export function makeNodeItemForNodeId(traceElements: LH.Artifacts.TraceElement[], nodeId: number | null | undefined): LH.Audit.Details.NodeValue | undefined;
//# sourceMappingURL=insight-audit.d.ts.map