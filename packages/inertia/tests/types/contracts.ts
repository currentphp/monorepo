import type { Current, Action, Subject } from '../../resources/types/current';
import './generated/methods';
import './generated/case-0';
import './generated/case-1';
import './generated/case-2';
import './generated/case-3';
import './generated/case-4';
import './generated/case-5';
import './generated/case-6';
import './generated/case-7';

declare const case0: Current<'primary', never>;
case0.menus.primary;
// @ts-expect-error Unselected output must not be accessible.
case0.title;
// @ts-expect-error Unselected output must not be accessible.
case0.description;
// @ts-expect-error Unselected output must not be accessible.
case0.subject?.title;
// @ts-expect-error Unselected output must not be accessible.
case0.subject?.description;
// @ts-expect-error Unselected output must not be accessible.
case0.actions;
declare const case1: Current<'primary', 'pagePresentation'>;
case1.menus.primary;
case1.title;
case1.description;
// @ts-expect-error Unselected output must not be accessible.
case1.subject?.title;
// @ts-expect-error Unselected output must not be accessible.
case1.subject?.description;
// @ts-expect-error Unselected output must not be accessible.
case1.actions;
declare const case2: Current<'primary', 'subjectPresentation'>;
case2.menus.primary;
// @ts-expect-error Unselected output must not be accessible.
case2.title;
// @ts-expect-error Unselected output must not be accessible.
case2.description;
case2.subject?.title;
case2.subject?.description;
// @ts-expect-error Unselected output must not be accessible.
case2.actions;
declare const case3: Current<'primary', 'pagePresentation' | 'subjectPresentation'>;
case3.menus.primary;
case3.title;
case3.description;
case3.subject?.title;
case3.subject?.description;
// @ts-expect-error Unselected output must not be accessible.
case3.actions;
declare const case4: Current<'primary', 'actions'>;
case4.menus.primary;
// @ts-expect-error Unselected output must not be accessible.
case4.title;
// @ts-expect-error Unselected output must not be accessible.
case4.description;
// @ts-expect-error Unselected output must not be accessible.
case4.subject?.title;
// @ts-expect-error Unselected output must not be accessible.
case4.subject?.description;
case4.actions;
declare const case5: Current<'primary', 'pagePresentation' | 'actions'>;
case5.menus.primary;
case5.title;
case5.description;
// @ts-expect-error Unselected output must not be accessible.
case5.subject?.title;
// @ts-expect-error Unselected output must not be accessible.
case5.subject?.description;
case5.actions;
declare const case6: Current<'primary', 'subjectPresentation' | 'actions'>;
case6.menus.primary;
// @ts-expect-error Unselected output must not be accessible.
case6.title;
// @ts-expect-error Unselected output must not be accessible.
case6.description;
case6.subject?.title;
case6.subject?.description;
case6.actions;
declare const case7: Current<'primary', 'pagePresentation' | 'subjectPresentation' | 'actions'>;
case7.menus.primary;
case7.title;
case7.description;
case7.subject?.title;
case7.subject?.description;
case7.actions;
// @ts-expect-error Unknown feature names are rejected.
type InvalidFeature = Current<'primary', 'everything'>;
declare const base: Current<'primary'>;
// @ts-expect-error Unknown menu names are rejected.
base.menus.footer;
declare const complete: Current<'primary', 'subjectPresentation' | 'pagePresentation' | 'actions'>;
const pageTitle: string | null = complete.title;
if (complete.subject) { const subjectTitle: string = complete.subject.title; }
const head: Action['method'] = 'HEAD';
// @ts-expect-error Core does not support OPTIONS actions.
const unsupported: Action['method'] = 'OPTIONS';
declare const subject: Subject;
// @ts-expect-error Base subject types do not promise selected presentation.
subject.title;
