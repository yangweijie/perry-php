import { t as TabProps } from "./Tabs-DfUZH0vL.js";
import * as _$vue from "vue";
import { PropType, SlotsType, VNode } from "vue";
import * as _$_vuepress_helper_client0 from "@vuepress/helper/client";

//#region src/client/components/CodeTabs.d.ts
declare const CodeTabs: _$vue.DefineComponent<_$vue.ExtractPropTypes<{
  /**
   * Active tab index
   *
   * 激活的标签页序号
   */
  active: {
    type: NumberConstructor;
    default: number;
  };
  /**
   * Code tab data
   *
   * 代码标签页数据
   */
  data: {
    type: PropType<TabProps[]>;
    required: true;
  };
  /**
   * Tab id
   *
   * 标签页 id
   */
  tabId: StringConstructor;
}>, () => VNode | null, {}, {}, {}, _$vue.ComponentOptionsMixin, _$vue.ComponentOptionsMixin, {}, string, _$vue.PublicProps, Readonly<_$vue.ExtractPropTypes<{
  /**
   * Active tab index
   *
   * 激活的标签页序号
   */
  active: {
    type: NumberConstructor;
    default: number;
  };
  /**
   * Code tab data
   *
   * 代码标签页数据
   */
  data: {
    type: PropType<TabProps[]>;
    required: true;
  };
  /**
   * Tab id
   *
   * 标签页 id
   */
  tabId: StringConstructor;
}>> & Readonly<{}>, {
  active: number;
}, SlotsType<{
  [slot: `title${number}`]: (props: {
    value: string;
    isActive: boolean;
  }) => _$_vuepress_helper_client0.RequiredSlotContent;
  [slot: `tab${number}`]: (props: {
    value: string;
    isActive: boolean;
  }) => _$_vuepress_helper_client0.RequiredSlotContent;
}>, {}, {}, string, _$vue.ComponentProvideOptions, true, {}, any>;
//#endregion
export { CodeTabs as t };
//# sourceMappingURL=CodeTabs-DaXkpoOH.d.ts.map