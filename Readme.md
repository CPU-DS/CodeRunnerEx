# CodeRunner-ex

## Introduction

This Moodle plugin extends the functions of [CodeRunner](https://github.com/trampgeek/moodle-qtype_coderunner) 
(a [Moodle](https://www.moodle.org) question type plugin that allows teachers to run a program in order to grade a student's answer), 
providing more features and customization options, mainly including: 

* More settings of CodeRunner-ex plugin, allowing administrator to set the default CodeRunner question type, editor size, grading method and so on. 
* Encryption Code and test result transfer between client and server, helping to work around security mechanism of some servers.
* Interactive result table with copy buttons, helping students to copy the long inputs/outputs of testcases to their own codes.
* AI code helper, helping students to analysis the question and their own codes with LLM model (requiring [Code-AiHelper](https://github.com/CPU-DS/Code-AiHelper) server on backend).

## Requirement

[Moodle](https://www.moodle.org) V4.0 or later, [CodeRunner](https://github.com/trampgeek/moodle-qtype_coderunner) v4 or later.

## Installation

Just install this plugin as other regular Moodle plugins.
After the installation of [Moodle](https://www.moodle.org) and [CodeRunner](https://github.com/trampgeek/moodle-qtype_coderunner), 
copy all files of this repo to question/type/coderunnerex directory of Moodle root 
(you may also need to change the ownership and access rights to ensure the directory and its contents are readable by the webserver). 
Then login to Moodle with an administrator account, follow the instructions of plugin upgrade.

## Usage

When creating new questions in the question bank page of Moodle, a new question type (CodeRunner-ex) should be available.
Just choose this type and configure the question as you like.